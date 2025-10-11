<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignLive;
use App\Models\QuishingCamp;
use App\Models\QuishingLiveCamp;
use App\Models\WaCampaign;
use App\Models\WaLiveCampaign;
use App\Services\CampaignTrainingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NoActivityUsersController extends Controller
{
    public function sendTrainingWithoutActivity(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:email,quishing,whatsapp',
                'campaign_live_id' => 'required'
            ]);

            $campaignUser = $this->getCampaignUser($request->campaign_live_id, $request->type);
            if (!$campaignUser) {
                return response()->json([
                    'success' => false,
                    'message' => __('Campaign user not found'),
                ], 404);
            }
            //check training assignment
            $trainingAssignment = $this->getTrainingAssignment($campaignUser, $request->type);

            if(!$trainingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => __('Training assignment not found for this campaign'),
                ], 404);
            }
            if($trainingAssignment == 'random'){
                $sent = CampaignTrainingService::assignTraining($campaignUser);
            }else{
                $trainings = $this->getTrainingsArray($campaignUser->campaign_id, $request->type);
                if (empty($trainings)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('No trainings found for this campaign'),
                    ], 404);
                }
                $sent = CampaignTrainingService::assignTraining($campaignUser, $trainings);
            }

            if(!$sent){
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to assign training'),
                ], 500);
            }

            $this->updateCampaignLive($campaignUser, $request->type);
            log_action("Training assigned successfully to $campaignUser->user_email");
            return response()->json([
                'success' => true,
                'message' => __('Training assigned successfully'),
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation error: ') . $e->validator->errors()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error: ') . $e->getMessage(),
            ], 500);
        }
    }

    private function updateCampaignLive($campaignUser, $type)
    {
        switch ($type) {
            case 'email':
                CampaignLive::find($campaignUser->id)->update([
                    'training_assigned' => 1
                ]);
                break;
            case 'quishing':
                QuishingLiveCamp::find($campaignUser->id)->update([
                    'training_assigned' => '1'
                ]);
                break;
            case 'whatsapp':
                WaLiveCampaign::find($campaignUser->id)->update([
                    'training_assigned' => 1
                ]);
                break;
            default:
                throw new \Exception('Invalid campaign type');
        }

        

    }

    private function getTrainingsArray($campaignId, $type)
    {
        switch ($type) {
            case 'email':
                $trainings = Campaign::where('campaign_id', $campaignId)->value('training_module');
                return json_decode($trainings, true) ?? [];
            case 'quishing':
                $trainings = QuishingCamp::where('campaign_id', $campaignId)->value('training_module');
                return json_decode($trainings, true) ?? [];
            case 'whatsapp':
                $trainings = WaCampaign::where('campaign_id', $campaignId)->value('training_module');
                return json_decode($trainings, true) ?? [];
            default:
                return [];
        }

    }

    private function getCampaignUser($campaignLiveId, $type)
    {
        switch ($type) {
            case 'email':
                return CampaignLive::find($campaignLiveId);
            case 'quishing':
                return QuishingLiveCamp::find($campaignLiveId);
            case 'whatsapp':
                return WaLiveCampaign::find($campaignLiveId);
            default:
                return null;
        }
    }

    private function getTrainingAssignment($campaignUser, $type)
    {
        switch ($type) {
            case 'email':
                return Campaign::where('campaign_id', $campaignUser->campaign_id)
                    ->value('training_assignment');
            case 'quishing':
                return QuishingCamp::where('campaign_id', $campaignUser->campaign_id)
                    ->value('training_assignment');
            case 'whatsapp':
                return WaCampaign::where('campaign_id', $campaignUser->campaign_id)
                    ->value('training_assignment');
            default:
                return null;
        }
    }
}
