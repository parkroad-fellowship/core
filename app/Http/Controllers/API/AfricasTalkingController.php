<?php

namespace App\Http\Controllers\API;

use AfricasTalking\SDK\AfricasTalking;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AfricasTalkingController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Africas Talking Controller || Index || ', $request->all());
        $at = new AfricasTalking(
            username: config('prf.app.africas_talking.username'),
            apiKey: config('prf.app.africas_talking.api_key')
        );

        $greetings = <<<EOT
            Hello, welcome to Parkroad Fellowship (PRF).
            Please enter your choice to continue.
            1. Missions 2. News
        EOT;

        $voice = $at->voice();
        $voiceActions = $voice->messageBuilder();

        $xmlResponse =  $voiceActions
            ->getDigits([
                'text' => $greetings,
                'numDigits' => 1,
                'timeout' => 10,
                'finishOnKey' => '#',
                'callbackUrl' => config('prf.app.africas_talking.callback_url') . '/api/v1/communications/africa-is-talking/route-call',
            ])->build();

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => $xmlResponse,
        ]);
    }

    public function routeCall(Request $request)
    {
        $validated = $request->all();
        Log::info('Africas Talking Controller || RouteCall || ', $validated);

        // $at = new AfricasTalking(
        //     username: config('prf.app.africas_talking.username'),
        //     apiKey: config('prf.app.africas_talking.api_key')
        // );

        $digits = $validated['dtmfDigits'];

        match ($digits) {
            '1' => $this->callMissions($request),
            '2' => $this->callMissions($request),
            '3' => $this->callMissions($request),
            '4' => $this->callMissions($request),
            '5' => $this->callMissions($request),
        };
    }

    public function callMissions(Request $request)
    {
        $validated = $request->all();
        Log::info('Africas Talking Controller || CallMissions || ', $validated);

        $at = new AfricasTalking(
            username: config('prf.app.africas_talking.username'),
            apiKey: config('prf.app.africas_talking.api_key')
        );

        $voice = $at->voice();
        $voice->call(
            from: config('prf.app.africas_talking.from'),
            to: config('prf.app.africas_talking.missions_desk')
        );
    }
}
