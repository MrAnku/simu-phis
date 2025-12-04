<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $title ?? 'Security Awareness' }}</title>
	<style>
		/* Simple responsive email styles */
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; margin:0; padding:0; background:#f5f7fb; }
		.container { max-width:600px; margin:24px auto; background:#ffffff; border-radius:6px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.08); }
		.header { padding:20px; background:#0b5fff; color:#fff; }
		.header h1 { margin:0; font-size:20px; }
		.preheader { display:none; font-size:1px; color:#fff; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden; }
		.content { padding:20px; color:#333; font-size:15px; line-height:1.5; }
		.section { margin-bottom:16px; }
		.section h2 { margin:0 0 8px 0; font-size:16px; color:#0b5fff; }
		.btn { display:inline-block; padding:10px 16px; background:#0b5fff; color:#fff; text-decoration:none; border-radius:4px; }
		.footer { padding:16px; font-size:13px; color:#777; background:#fafafa; text-align:center; }
		@media only screen and (max-width:480px) {
			.container { margin:12px; }
			.header h1 { font-size:18px; }
		}
	</style>
</head>
<body>
	<div class="single-content" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial; padding:20px; background:#ffffff;">
		{!! $contentData ?? '' !!}
	</div>
</body>
