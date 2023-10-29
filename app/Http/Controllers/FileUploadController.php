<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FileUploadController extends Controller
{
    public function stringIsAValidUrl($s)
    {
        return filter_var($s, FILTER_VALIDATE_URL) !== false;
    }

    public function downloadFile($givenURL)
    {
        $fileContent = Http::get($givenURL)->body();
        $fileName = basename(parse_url($givenURL, PHP_URL_PATH));
        file_put_contents(public_path("processedFiles/{$fileName}"), $fileContent);
        return [
            'buffer' => $fileContent,
            'originalname' => $fileName,
        ];
    }

    public function getFileSize($res)
    {
        $size = $res->header('content-length');
        return $size;
    }

    public function generateFilesArray($files)
    {
        $filesArray = [];
        foreach ($files as $file) {
            $currentFile = $this->downloadFile($file);
            $filesArray[] = $currentFile;
        }
        return $filesArray;
    }

    public function fileIsAllowed(Request $request)
    {
        try {
            $maxSizeInMb = (int) $request->input('maxSizeInMb');
            $maxSizeinBytes = $maxSizeInMb * 1024 * 1024;
            $formatsAllow = array_map('strtolower', $request->input('formatsAllow'));

            $fileURL = $request->input('url');
            if (!$this->stringIsAValidUrl($fileURL)) {
                return response()->json([
                    'isSuccess' => false,
                    'error' => [
                        'messageEN' => 'Invalid Value, Please attach a file',
                        'messageAR' => 'المدخل غير صحيح, الرجاء إرفاق ملف',
                    ],
                ]);
            }
            $fileRes = Http::get($fileURL);

            $fileFormat = pathinfo(parse_url($fileURL, PHP_URL_PATH), PATHINFO_EXTENSION);
            $fileFormat = $fileFormat ? strtolower($fileFormat) : $fileFormat;
            $formatIsAllowed = in_array($fileFormat, $formatsAllow);
            if (!$formatIsAllowed) {
                return response()->json([
                    'isSuccess' => false,
                    'error' => [
                        'messageEN' => 'File format is not allowed',
                        'messageAR' => 'نوع الملف غير مسموح به',
                    ],
                ]);
            }

            $fileSize = $this->getFileSize($fileRes);
            if ($fileSize > $maxSizeinBytes) {
                return response()->json([
                    'isSuccess' => false,
                    'error' => [
                        'messageEN' => 'File size is too large',
                        'messageAR' => 'حجم الملف كبير جدا',
                    ],
                ]);
            }

            return response()->json([
                'isSuccess' => true,
                'error' => null,
            ]);
        } catch (\Exception $error) {
            return response()->json([
                'isSuccess' => false,
                'error' => [
                    'messageEN' => 'Something went wrong',
                    'messageAR' => 'حدث خطأ ما',
                ],
            ]);
        }
    }
 
    public function saveDispute(Request $request)
    {
        try {
            // Extract data from the request
            $token = $request->input('token');
            $segmentId = (string) $request->input('segmentId');
            $memberCode = (string) $request->input('memberCode');
            $memberNameEn = (string) $request->input('memberNameEn');
            $memberNameAr = (string) $request->input('memberNameAr');
            $productValue = (string) $request->input('productValue');
            $reasonCode = (string) $request->input('reasonCode');
            $reasonEn = (string) $request->input('reasonEn');
            $reasonAr = (string) $request->input('reasonAr');
            $fieldCode = (string) $request->input('fieldCode');
            $correctedValue = (string) $request->input('correctedValue');
            $disputeDescription = (string) $request->input('disputeDescription');
            $attachCodes = $request->input('attachCodes');
            $attachments = $this->processAttachments($request->input('attachments')); // Process attachments

            $customerTypeId = (string) $request->input('customerTypeId');
            $canConfirm = (string) $request->input('canConfirm');
            $dataTypeId = $request->input('dataTypeId');
            $isNotify = (string) $request->input('isNotify');
            $notifyMemberCode = (string) $request->input('notifyMemberCode');
            $BeneficiaryTypeId = $request->input('BeneficiaryTypeId');

            $molimBaseURL = "https://molimqapi.simah.com";
            $saveDisputeURL = "{$molimBaseURL}/api/v1/dispute/Save";
            
            // Create form data
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
                'attachments' => $attachments, // Attach processed attachments
                'attachCodes' => $attachCodes,
                'customerTypeId' => $customerTypeId,
                'canConfirm' => $canConfirm,
                'dataTypeId' => $dataTypeId !== null ? $dataTypeId : "",
                'isNotify' => $isNotify,
                'notifyMemberCode' => $notifyMemberCode,
                'BeneficiaryTypeId' => $BeneficiaryTypeId !== null ? $BeneficiaryTypeId : "",
            ];

            // Make the POST request with headers
            $response = Http::withHeaders([
                'Content-Type' => 'multipart/form-data',
                'language' => 'en',
                'appId' => '5',
                'Authorization' => "Bearer $token",
            ])->attach($formData)->post($saveDisputeURL);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $error) {
            return response()->json([
                'message' => "Error: $error",
            ], 500);
        }
    }

    public function submitDocuments(Request $request)
    {
        try {
            // Extract data from the request
            $token = $request->input('token');
            $disputeId = $request->input('disputeId');
            $comment = $request->input('comment');
            $attachments = $this->processAttachments($request->input('attachments')); // Process attachments

            $molimBaseURL = "https://molimqapi.simah.com";
            $submitDocumentURL = "{$molimBaseURL}/api/v1/dispute/Submit/Document";

            // Create form data
            $formData = [
                'disputeId' => $disputeId,
                'comment' => $comment,
                'attachments' => $attachments, // Attach processed attachments
            ];

            // Make the POST request with headers
            $response = Http::withHeaders([
                'Content-Type' => 'multipart/form-data',
                'language' => 'en',
                'appId' => '5',
                'Authorization' => "Bearer $token",
            ])->attach($formData)->post($submitDocumentURL);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $error) {
            return response()->json([
                'message' => "Error: $error",
            ], 500);
        }
    }

       // Function to process attachments and return them as an array
 // Function to process attachments and return them as an array
// Modify the processAttachments method to prepare attachments in the expected format
private function processAttachments($attachments)
{
    $filesArray = [];

    foreach ($attachments as $attachment) {
        $filesArray[] = [
            'name' => 'attachments[]',
            'contents' => $attachment['buffer'], // Assuming 'buffer' contains file content
            'filename' => $attachment['originalname'], // Assuming 'originalname' contains the file name
        ];
    }

    return $filesArray;
}



}
