<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDO;
use DateTime;


class CheckFeedbackController extends Controller
{
    public function index()
    {
        ini_set('memory_limit', '8192M');

        $query = DB::connection('BTID')
        ->table('mgr.cb_cash_request_appr')
        ->where('mgr.cb_cash_request_appr.status', '=', 'A')
        ->whereDay('mgr.cb_cash_request_appr.approved_date', '=', now()->day)
        ->whereMonth('mgr.cb_cash_request_appr.approved_date', '=', now()->month)
        ->whereYear('mgr.cb_cash_request_appr.approved_date', '=', now()->year)
        ->where('mgr.cb_cash_request_appr.level_no', '=', function ($query) {
            $query->select(DB::raw('MAX(a.level_no)'))
                ->from('mgr.cb_cash_request_appr as a')
                ->whereColumn('a.entity_cd', '=', 'mgr.cb_cash_request_appr.entity_cd')
                ->whereColumn('a.approve_seq', '=', 'mgr.cb_cash_request_appr.approve_seq')
                ->whereColumn('a.doc_no', '=', 'mgr.cb_cash_request_appr.doc_no')
                ->whereColumn('a.module', '=', 'mgr.cb_cash_request_appr.module')
                ->whereColumn('a.request_type', '=', 'mgr.cb_cash_request_appr.request_type');
        })
        ->orderByDesc('mgr.cb_cash_request_appr.approved_date')
        ->get();


        foreach ($query as $data){
            $approve_seq = $data->approve_seq;
            $trim_approve_seq = rtrim($approve_seq);
            $entity_cd = $data->entity_cd;
            $trim_entity_cd = rtrim($entity_cd);
            $doc_no = $data->doc_no;
            $trim_doc_no = rtrim($doc_no);
            $status = $data->status;
            $trim_status = rtrim($status);
            $type = $data->TYPE;
            $trim_type = rtrim($type);
            $module = $data->module;
            $trim_module = rtrim($module);
            $approved_date = $data->approved_date;
            $dateTime_app = new DateTime($approved_date);
            $exploded_values = explode(" ", $entity_cd);
            $descs = '(APPROVED)';
            
            $formatted_date = $dateTime_app->format('Ymd');
            $supervisor = 'Y';
            $reason = '0';
            if ($trim_type == 'E' && $trim_module == "CB")
            {
                $descsLong = 'Propose Transfer to Bank';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_fupd';
                $folder = 'feedbackCbFupd';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'U' && $trim_module == "CB")
            {
                $descsLong = 'Payment Request';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_ppu';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'V' && $trim_module == "CB")
            {
                $descsLong = 'Payment Request';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_ppu_vvip';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'D' && $trim_module == "CB")
            {
                $descsLong = 'Recapitulation Bank';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_rpb';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'D' && $trim_module == "CB")
            {
                $descsLong = 'Cash Advance Settlement';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_rum';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'A' && $trim_module == "PO") 
            {
                $descsLong = 'Purchase Order';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_po_order';
                $folder = 'feedbackPoOrder';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'Q' && $trim_module == "PO") 
            {
                $descsLong = 'Purchase Requisition';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_po_request';
                $folder = 'feedbackPOR';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'S' && $trim_module == "PO") 
            {
                $descsLong = 'Purchase Selection';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_po_selection';
                $folder = 'feedbackPOS';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'A' && $trim_module == 'CM') 
            {
                $descsLong = 'Contract Progress';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'B' && $trim_module == 'CM') 
            {
                $descsLong = 'Contract Complete';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'C' && $trim_module == 'CM') 
            {
                $descsLong = 'Warranty Complete';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'D' && $trim_module == 'CM') 
            {
                $descsLong = 'Varian Order';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            } 
            else if ($trim_type == 'E' && $trim_module == 'CM') 
            {
                $descsLong = 'Contract Entry';
                $cacheFile = 'email_feedback_sent_' . $trim_approve_seq . '_' . $trim_entity_cd . '_' . $trim_doc_no . '_' . $trim_status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback';
                $folder = 'feedbackCb';
                $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $formatted_date . '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }
            
                if (!file_exists($cacheFilePath)) {
                    var_dump($doc_no);
                }
            }
        }
    }
}
