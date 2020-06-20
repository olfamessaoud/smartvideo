<?php


namespace content\smartvideo\Controllers;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stevebauman\Location;
use content\smartvideo\trax_xapiserver_statements;  

class connectController extends Controller
{
    public function create(Request $request)
    {
      $request->validate([
        'userID' => 'required|integer|min:1',
        'timestamp' => 'bail|nullable|date_format:Y-m-d\TH:i:sP'
    ]);
        
    $post_data = connectController::formData($request);
    $statement = new trax_xapiserver_statements ([
        'data' => $post_data
    ]);
    $statement->save();
    return response()->json([ 'message' => 'Statement added successfully' ], 201); 
    }

    public function list()
    {
        $post = trax_xapiserver_statements::all();
        return response()->json($post);
    }

    public function show($id)
    {
        if (trax_xapiserver_statements::where('id', $id)->exists()) {
          $statement = trax_xapiserver_statements::find($id);
          return response()->json($statement);
          } else {
            return response()->json([
              'message' => 'Statement not found'
            ], 404);
          } 
    }


    public function update(Request $request, $id)
    {
      $request->validate([
        'userID' => 'required|integer|min:1',
        'timestamp' => 'bail|nullable|date_format:Y-m-d\TH:i:sP'
    ]);

    if (trax_xapiserver_statements::where('id', $id)->exists()) {
      $statement = trax_xapiserver_statements::find($id);
      $post_data = connectController::formData($request);
      $statement->data = $post_data;
      $statement->save();
      return response()->json([
        'message' => 'Statement updated successfully'
      ], 200);
    } else {
      return response()->json([
        'message' => 'Statement not found'
      ], 404);
    }
}

    public function destroy($id)
    {
        if(trax_xapiserver_statements::where('id', $id)->exists()) {
            $statement = trax_xapiserver_statements::where('id','=',$id)->first();
            $statement->delete();
            return response()->json([
              'message' => 'Statement deleted'
            ], 202);
          } else {
            return response()->json([
              'message' => 'Statement not found'
            ], 404);
          }
    }

	public function formData(Request $request)
	{
    if ($request->timestamp == null) {
        $request->timestamp = (Carbon::now())->format('Y-m-d\TH:i:sP');
    }

    $ip = trim(shell_exec("dig +short myip.opendns.com @resolver1.opendns.com"));
    $position = \Location::get($ip);
    $coordinates = array((float)$position->latitude, (float)$position->longitude);
    $coordinates = json_encode($coordinates);

      $data = [

        'verb' => [
        'id' => 'https://smartvideo.fr/xapi/video/verbs/connected',
        'display' => [
          'en-US' => 'connected'
        ],
      ],
      'actor' => [
        'mbox' => 'mailto:user#'.$request->userID.'@smartvideo.fr',
        'objectType' => 'Agent'
      ],
      'object' => [
        'id' => 'https://smartvideo.fr/xapi/video/objects/void',
        'objectType'  => 'Activity'
      ],
      'context' => [
        'extensions' => [
                  'https://smartvideo.fr/xapi/video/extensions/geojson' => [
                      'type' => 'Feature',
                      'geometry' => [
                        'type' => 'Point',
                        'coordinates' => $coordinates                            
                        ] 
                    ]
                ]
        
      ],
      'timestamp' => $request->timestamp

      ];

    header('Content-type: text/javascript');
    $post_data = json_encode($data, JSON_FORCE_OBJECT | JSON_PRETTY_PRINT |
               JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    return $post_data;
  }

}


