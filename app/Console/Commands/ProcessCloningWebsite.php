<?php

namespace App\Console\Commands;

use App\Models\WebsiteCloneJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class ProcessCloningWebsite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-cloning-website';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', 300);

        // Create job record
        $jobRecord = WebsiteCloneJob::where('status', 'pending')->first();
        if (!$jobRecord) {
            return;
        }
        $jobRecord->update(['status' => 'processing']);
        try {
            $response = Http::get($jobRecord->url);
            if (!$response->successful()) {
                $jobRecord->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to fetch the URL',
                ]);
                return;
            }

            $html = $response->body();
            $crawler = new Crawler($html, $jobRecord->url);

            // Remove script, meta, and link tags that are not rel="stylesheet"
            $crawler->filter('script, meta, link:not([rel="stylesheet"])')->each(function ($node) {
                $domNode = $node->getNode(0);
                if ($domNode && $domNode->parentNode) {
                    $domNode->parentNode->removeChild($domNode);
                }
            });

            $baseUrl = parse_url($jobRecord->url, PHP_URL_SCHEME) . '://' . parse_url($jobRecord->url, PHP_URL_HOST);

            $assetUrls = [];

            // Extract asset links (only CSS links, images, and videos)
            $crawler->filter('link[rel="stylesheet"], img, video')->each(function ($node) use (&$assetUrls, $baseUrl) {
                $tagName = $node->nodeName();
                $attr = $tagName === 'link' ? 'href' : 'src';
                $src = $node->attr($attr);
                if ($src) {
                    $absoluteUrl = $this->makeAbsoluteUrl($src, $baseUrl);
                    $assetUrls[$src] = $absoluteUrl;
                }
            });

            // Download and re-upload assets to S3
            $cloudfrontBaseUrl = env('CLOUDFRONT_URL');
            $s3Urls = [];

            foreach ($assetUrls as $original => $assetUrl) {
                $assetResponse = @file_get_contents($assetUrl);
                if ($assetResponse === false) continue;

                $pathInfo = pathinfo(parse_url($assetUrl, PHP_URL_PATH));
                $extension = $pathInfo['extension'] ?? 'bin';
                $s3Path = 'clones/' . $jobRecord->company_id . '/' . uniqid() . '.' . $extension;

                Storage::disk('s3')->put($s3Path, $assetResponse);

                // Use CloudFront URL
                $s3Urls[$original] = $cloudfrontBaseUrl . '/' . $s3Path;
            }

            // Replace URLs in HTML
            $html = $crawler->html();
            foreach ($s3Urls as $old => $new) {
                $html = str_replace($old, $new, $html);
            }

            // Ensure the HTML is clean by re-parsing and removing any residual unwanted tags
            $cleanCrawler = new Crawler($html);
            $cleanCrawler->filter('script, meta, link:not([rel="stylesheet"])')->each(function ($node) {
                $domNode = $node->getNode(0);
                if ($domNode && $domNode->parentNode) {
                    $domNode->parentNode->removeChild($domNode);
                }
            });

            // Get the final cleaned HTML
            $finalHtml = $cleanCrawler->html();

            // Save the final HTML
            $filename = 'cloned_sites/' . $jobRecord->company_id . '/' . md5($jobRecord->url) . '.html';
            Storage::disk('s3')->put($filename, $finalHtml);

            // Update job record
            $jobRecord->update([
                'status' => 'completed',
                'file_url' => $cloudfrontBaseUrl . '/' . $filename,
            ]);
        } catch (\Exception $e) {
            $jobRecord->update([
                'status' => 'failed',
                'error_message' => 'Exception: ' . $e->getMessage(),
            ]);
        }
    }

    protected function makeAbsoluteUrl($url, $baseUrl)
    {
        if (parse_url($url, PHP_URL_SCHEME) !== null) {
            return $url;
        }

        if (strpos($url, '//') === 0) {
            return 'http:' . $url;
        }

        if ($url[0] === '/') {
            return $baseUrl . $url;
        }

        return $baseUrl . '/' . $url;
    }
}
