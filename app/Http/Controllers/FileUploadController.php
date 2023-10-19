<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
            $attachCodes = $request->input('attachCodes');
            $attachments = $request->input('attachments');
            $customerTypeId = $request->input('customerTypeId');
            $canConfirm = $request->input('canConfirm');
            $dataTypeId = $request->input('dataTypeId');
            $isNotify = $request->input('isNotify');
            $notifyMemberCode = $request->input('notifyMemberCode');
            $BeneficiaryTypeId = $request->input('BeneficiaryTypeId');

            // Generate the array of files
            $filesArray = $this->generateFilesArray($attachments);

            $molimBaseURL = "https://molimqapi.simah.com";
            $saveDisputeURL = "{$molimBaseURL}/api/v1/dispute/Save";

            $formData = [
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
            ];

            // Append files to the form data
            foreach ($filesArray as $index => $file) {
                $formData["attachments[{$index}]"] = [
                    'name' => 'attachments[]',
                    'contents' => $file,
                    'filename' => 'file_' . $index,
                ];
            }

            // Append attachCodes
            foreach ($attachCodes as $index => $code) {
                $formData["attachCodes[{$index}]"] = $code;
            }

            $formData['customerTypeId'] = $customerTypeId;
            $formData['canConfirm'] = $canConfirm;

            if ($dataTypeId !== null && $dataTypeId !== '') {
                $formData['dataTypeId'] = $dataTypeId;
            }

            $formData['isNotify'] = $isNotify;
            $formData['notifyMemberCode'] = $notifyMemberCode;

            if ($BeneficiaryTypeId !== null && $BeneficiaryTypeId !== '') {
                $formData['BeneficiaryTypeId'] = $BeneficiaryTypeId;
            }

            // Send the request
            $response = Http::withHeaders([
                'language' => 'en',
                'appId' => '5',
                'Authorization' => 'Bearer ' . $token,
            ])->asForm()->post($saveDisputeURL, $formData);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $error->getMessage(),
                'body' => $request->all(),
            ], 500);
        }
    }

    private function generateFilesArray($attachments)
    {
        $filesArray = [];

        foreach ($attachments as $attachment) {
            // Process each attachment and create an array with buffer and original name.
            $fileBuffer = file_get_contents($attachment);
            $originalName = pathinfo($attachment, PATHINFO_BASENAME);

            $filesArray[] = $fileBuffer;
        }

        return $filesArray;
    }


    public function submitDocuments(Request $request)
    {
        try {
            // Access request data and files
            $disputeId = $request->input('disputeId');
            $comment = $request->input('comment');
            $attachments = $request->file('attachments');

            // Process files
            $formData = [
                'disputeId' => $disputeId,
                'comment' => $comment,
            ];

            foreach ($attachments as $attachment) {
                // Upload each attachment to the Molim API
                $attachmentFileName = $attachment->getClientOriginalName();
                $attachmentContents = file_get_contents($attachment->getRealPath());
                $formData['attachments'][] = [
                    'name' => 'attachments',
                    'contents' => $attachmentContents,
                    'filename' => $attachmentFileName,
                ];
            }

            // Make a POST request to the Molim API using Laravel's HTTP client
            $response = Http::withHeaders([
                'Content-Type' => 'multipart/form-data',
                'language' => 'en',
                'appId' => '5',
            ])->attachMultipart($formData)->post('https://molimqapi.simah.com/api/v1/dispute/Submit/Document', [
                'headers' => ['Authorization' => 'Bearer ' . $request->input('token')],
            ]);

            // Process the response and return it
            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'isSuccess' => false,
                'error' => [
                    'messageEN' => 'Something went wrong',
                    'messageAR' => 'حدث خطأ ما',
                ],
            ]);
        }
    }

    
    
    
    
    private function prepareFilesArray($attachments)
{
    $filesArray = [];

    foreach ($attachments as $attachment) {
        $fileBuffer = file_get_contents($attachment);
        $originalName = pathinfo($attachment, PATHINFO_BASENAME);

        $filesArray[] = [
            'buffer' => $fileBuffer,
            'originalname' => $originalName,
        ];
    }

    return $filesArray;
}


    // private function generateFilesArray($attachments)
    // {
    //     $filesArray = [];

    //     foreach ($attachments as $attachment) {
    //         $fileBuffer = file_get_contents($attachment);
    //         $originalName = pathinfo($attachment, PATHINFO_BASENAME);

    //         $filesArray[] = [
    //             'buffer' => $fileBuffer,
    //             'originalname' => $originalName,
    //         ];
    //     }

    //     return $filesArray;
    // }
}
