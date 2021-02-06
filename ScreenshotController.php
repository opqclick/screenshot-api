<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Models\Request as ApiRequest;
use Spatie\Browsershot\Browsershot;
use Spatie\Image\Manipulations;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScreenshotController extends Controller
{
    private $browsershot;

    /**
     * ScreenshotController constructor.
     * @param Browsershot $browsershot
     */
    public function __construct(Browsershot $browsershot)
    {
        $this->browsershot = $browsershot;
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Spatie\Image\Exceptions\InvalidManipulation
     */
    public function getCreateScreenshot(Request $request)
    {
        $this->validate($request, [
            'api_key' => 'required|exists:clients,api_key',
            'dimension' => 'required',
            'hash' => 'required',
            'delay' => 'required',
            'url' => 'required'
        ]);

        /*
         * Fetch client details.
         */
        $clientDetails = Client::where('api_key', $request->api_key)->first();

        /*
         * Create new entry for every request.
         */
        $newRequest = new ApiRequest();
        $newRequest->client_id = $clientDetails->client_id;
        $newRequest->dimension = $request->dimension;
        $newRequest->hash = $request->hash;
        $newRequest->delay = $request->delay;
        $newRequest->url = $request->url;
        $newRequest->created_at = Carbon::now();
        $newRequest->save();

        /*
         * API response functions
         */

        //A.Invalid Argument Hash or Does not exists
        if(!$this->checkHash($request->url, $request->hash)){
            $invalid_hash_filename   = 'invalid_hash.png';
            $invalid_hash_filePath   =   base_path('public/images/response/');
            $invalid_hash_image = $invalid_hash_filePath.$invalid_hash_filename;

            //dd($img);

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_hash_image, 200, $headers);

            return $response;
        }

        //B.Invalid Argument URL or Does not exists
        //dd(!$this->checkUrl($request->url));
        if(!$this->checkUrl($request->url)){
            $invalid_url_filename   = 'invalid_url.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }

        //C. Invalid Argument Dimension
        if(!preg_match('/(\d+)x(\d+)/', $request->dimension, $dimensions)){
            $invalid_url_filename   = 'invalid_dimension.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }

        //C. Invalid Argument Delay
        if(!preg_match('/(\d+)/', $request->delay, $delays)){
            $invalid_url_filename   = 'invalid_delay.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }


        //D. System Error or Return response screenshot
        try{
            if(!empty($delays)){
                $delayInMilliseconds = $delays[0];
            }else{
                $delayInMilliseconds = 200;
            }

            if(!empty($dimensions)){
                $requestedWidth = $dimensions[1];

                $requestedHeight = $dimensions[2];
            }

            if(isset($requestedWidth)){
                $width = $requestedWidth;
            }else{
                $width = 800;
            }

            if(isset($requestedHeight)){
                $height = $requestedHeight;
            }else{
                $height = 600;
            }

            $url    =   $request->url;
            $filename   = md5(time()).'.jpg';
            $pathToImage   =   base_path('public/uploads/screenshots/');

            $this->browsershot->setURL($url)
                ->select('body')
                //windowSize is for in which screen size the give URL will load
                ->windowSize(1024, 768)
                //fit will return the image as given size
                ->fit(Manipulations::FIT_CONTAIN, $width, $height)
                ->setDelay($delayInMilliseconds)
                ->save($pathToImage.$filename);

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];
            $image = $pathToImage.$filename;

            $response = new BinaryFileResponse($image, 202, $headers);

            return $response;
        }catch (Exception $exception){
            $invalid_url_filename   = 'system_error.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }
    }

    protected function buildFailedValidationResponse(Request $request, array $errors) {

        if(isset($errors['api_key'])){
            $invalid_url_filename   = 'parameter_api_key_missing.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            //dd($img);

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }

        if(isset($errors['dimension'])){
            $invalid_url_filename   = 'parameter_dimension_missing.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            //dd($img);

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }

        if(isset($errors['hash'])){
            $invalid_url_filename   = 'parameter_hash_missing.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            //dd($img);

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }

        if(isset($errors['delay'])){
            $invalid_url_filename   = 'parameter_delay_missing.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            //dd($img);

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }

        if(isset($errors['url'])){
            $invalid_url_filename   = 'parameter_url_missing.png';
            $invalid_url_filePath   =   base_path('public/images/response/');
            $invalid_url_image = $invalid_url_filePath.$invalid_url_filename;

            //dd($img);

            $type = 'image/jpg';
            $headers = ['Content-Type' => $type];

            $response = new BinaryFileResponse($invalid_url_image, 200, $headers);

            return $response;
        }

        //return ["code"=> 406 , "message" => "forbidden" , "errors" =>$errors];
    }

    public function checkHash($url, $clientHash)
    {
        /*
         * Own hashing algorithm
         */
        $secret = "TOP_SECRET";
        $systemOwnHash = md5($url.$secret);
        //dd($systemOwnHash);

        if($systemOwnHash == $clientHash){
            return true;
        }

        return false;
    }

    public function checkUrl($url)
    {
        $array = get_headers($url);
        $string = $array[0];
        if(strpos($string,"200"))
        {
            return true;
        }else{
            return false;
        }
    }


}
