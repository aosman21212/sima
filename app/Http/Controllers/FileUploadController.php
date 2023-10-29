<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller; // Make sure to import the Controller class

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
            $attachments = $request->input('attachments');

            $customerTypeId = (string) $request->input('customerTypeId');
            $canConfirm = (string) $request->input('canConfirm');
            $dataTypeId = $request->input('dataTypeId');
            $isNotify = (string) $request->input('isNotify');
            $notifyMemberCode = (string) $request->input('notifyMemberCode');
            $BeneficiaryTypeId = $request->input('BeneficiaryTypeId');

            $filesArray = $this->generateFilesArray($attachments);
            $molimBaseURL = "https://molimqapi.simah.com";
            $saveDisputeURL = "{$molimBaseURL}/api/v1/dispute/Save";
            $formData = new FormData();
            $formData->append('segmentId', $segmentId);
            $formData->append('memberCode', $memberCode);
            $formData->append('memberNameEn', $memberNameEn);
            $formData->append('memberNameAr', $memberNameAr);
            $formData->append('productValue', $productValue);
            $formData->append('reasonCode', $reasonCode);
            $formData->append('reasonEn', $reasonEn);
            $formData->append('reasonAr', $reasonAr);
            $formData->append('fieldCode', $fieldCode);
            $formData->append('correctedValue', $correctedValue);
            $formData->append('disputeDescription', $disputeDescription);

            foreach ($filesArray as $currentFile) {
                $fileName = $currentFile['originalname'];
                $fileBuffer = $currentFile['buffer'];
                $formData->append('attachments[]', $fileBuffer, $fileName);
            }

            foreach ($attachCodes as $attachCode) {
                $formData->append('attachCodes[]', $attachCode);
            }

            $formData->append('customerTypeId', $customerTypeId);
            $formData->append('canConfirm', $canConfirm);

            if ($dataTypeId !== null && $dataTypeId !== "") {
                $formData->append('dataTypeId', $dataTypeId);
            }

            $formData->append('isNotify', $isNotify);
            $formData->append('notifyMemberCode', $notifyMemberCode);

            if ($BeneficiaryTypeId !== null && $BeneficiaryTypeId !== "") {
                $formData->append('BeneficiaryTypeId', $BeneficiaryTypeId);
            }

            $response = Http::withHeaders([
                'Content-Type' => 'multipart/form-data',
                'language' => 'en',
                'appId' => '5',
                'Authorization' => "Bearer $token",
            ])->post($saveDisputeURL, $formData);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $error) {
            return response()->json([
                'message' => "error is $error",
                'body' => $request->all(),
            ], 500);
        }
    }

    public function submitDocuments(Request $request)
    {
        try {
            $token = $request->input('token');
            $disputeId = $request->input('disputeId');
            $comment = $request->input('comment');
            $attachCodes = $request->input('attachCodes');
            $attachments = $request->input('attachments');

            $filesArray = $this->generateFilesArray($attachments);
            $molimBaseURL = "https://molimqapi.simah.com";
            $saveDisputeURL = "{$molimBaseURL}/api/v1/dispute/Submit/Document";
            $formData = new FormData();
            $formData->append('disputeId', $disputeId);
            $formData->append('comment', $comment);

            foreach ($filesArray as $currentFile) {
                $fileName = $currentFile['originalname'];
                $fileBuffer = $currentFile['buffer'];
                $formData->append('attachments[]', $fileBuffer, $fileName);
            }

            foreach ($attachCodes as $attachCode) {
                $formData->append('attachCodes[]', $attachCode);
            }

            $response = Http::withHeaders([
                'Content-Type' => 'multipart/form-data',
                'language' => 'en',
                'appId' => '5',
                'Authorization' => "Bearer $token",
            ])->post($saveDisputeURL, $formData);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $error) {
            return response()->json([
                'message' => "error is $error",
                'body' => $request->all(),
            ], 500);
        }
    }
}
