<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;


class FileUploadController extends Controller
{
    public function checkFileIsAllowed(Request $request)
    {
        try {
            $maxSizeInMb = (int)$request->input('maxSizeInMb');
            $maxSizeInBytes = $maxSizeInMb * 1024 * 1024;
            $formatsAllow = array_map('strtolower', $request->input('formatsAllow'));
            $fileURL = $request->input('url');

            // Validate the URL
            if (!filter_var($fileURL, FILTER_VALIDATE_URL)) {
                return response()->json([
                    'isSuccess' => false,
                    'error' => [
                        'messageEN' => 'Invalid Value, Please attach a file',
                        'messageAR' => 'المدخل غير صحيح, الرجاء إرفاق ملف',
                    ],
                ], 200);
            }

            // Fetch the file and perform validation
            $fileRes = Http::get($fileURL);
            // ... Implement file format and size validation as in your Node.js code ...

            return response()->json([
                'isSuccess' => true,
                'error' => null,
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'isSuccess' => false,
                'error' => [
                    'messageEN' => 'Something went wrong',
                    'messageAR' => 'حدث خطأ ما',
                ],
            ], 200);
        }
    }

 


   
    public function saveDispute(Request $request)
    {
        try {
            // Retrieve data from the request
            $token = $request->input('token');
            $segmentId = $request->input('segmentId');
            $memberCode = $request->input('memberCode');
            $memberNameEn = $request->input('memberNameEn');
            $memberNameAr = $request->input('memberNameAr');
            $productValue = $request->input('productValue');
            $reasonCode = $request->input('reasonCode');
            $reasonEn = $request->input('reasonEn');
            $reasonAr = $request->input('reasonAr');
            $fieldCode = $request->input('fieldCode');
            $correctedValue = $request->input('correctedValue');
            $disputeDescription = $request->input('disputeDescription');
            $customerTypeId = $request->input('customerTypeId');
            $canConfirm = $request->input('canConfirm');
            $dataTypeId = $request->input('dataTypeId');
            $isNotify = $request->input('isNotify');
            $notifyMemberCode = $request->input('notifyMemberCode');
            $BeneficiaryTypeId = $request->input('BeneficiaryTypeId');

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                $file = $request->file('attachments');
                $fileContents = file_get_contents($file);
                $fileName = $file->getClientOriginalName();

                // Prepare the data for the external API
                $data = [
                    'segmentId' => $segmentId,
                    'memberCode' => $memberCode,
                    'memberNameEn' => $memberNameEn,
                    'memberNameAr' => $memberNameAr,
                    'productValue' => $productValue,
                    'reasonCode' => $reasonCode,
                    'reasonEn' => $reasonEn,
                    'reasonAr' => $reasonAr,
                    'fieldCode' => $fieldCode,
                    'correctedValue' => $correctedValue,
                    'disputeDescription' => $disputeDescription,
                    'customerTypeId' => $customerTypeId,
                    'canConfirm' => $canConfirm,
                    'isNotify' => $isNotify,
                    'notifyMemberCode' => $notifyMemberCode,
                ];

                // Handle optional fields if they exist
                if ($dataTypeId) {
                    $data['dataTypeId'] = $dataTypeId;
                }

                if ($BeneficiaryTypeId !== null && $BeneficiaryTypeId !== "") {
                    $data['BeneficiaryTypeId'] = $BeneficiaryTypeId;
                }

                // Send a POST request to the external API
                $response = Http::withHeaders([
                    'Content-Type' => 'multipart/form-data',
                    'language' => 'en',
                    'appId' => '5',
                    'Authorization' => 'Bearer ' . $token,
                ])->attach('attachments', $fileContents, $fileName)
                    ->post('https://molimqapi.simah.com/api/v1/dispute/Save', $data);

                // Handle the API response
                $responseData = $response->json();

                return response()->json($responseData, $response->status());
            } else {
                return response()->json(['message' => 'No file uploaded'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    
    
  
    public function submitDocuments(Request $request)
    {
        try {
            $token = 'your_token';
            $disputeId = 'your_dispute_id';
            $comment = 'your_comment';
    
            $attachmentUrls = [
                'https://www.africau.edu/images/default/sample.pdf',
            ];
    
            $molimBaseURL = 'https://molimqapi.simah.com';
            $submitDocumentsURL = "{$molimBaseURL}/api/v1/dispute/Submit/Document";
    
            $requestPayload = [
                'disputeId' => $disputeId,
                'comment' => $comment,
                'attachments' => $attachmentUrls, // Include the attachment URLs directly
            ];
    
            $response = Http::withHeaders([
                'language' => 'en',
                'appId' => '5',
                'Authorization' => 'Bearer ' . $token,
            ])->post($submitDocumentsURL, $requestPayload);
    
            return response()->json($response->json(), $response->status());
        } catch (\Exception $error) {
            return response()->json([
                'message' => "Error: {$error->getMessage()}",
            ], 500);
        }
    }
    
   
    
    
    
    
    
    
    
    
    
    
    
    
    

    
    
    

}
    