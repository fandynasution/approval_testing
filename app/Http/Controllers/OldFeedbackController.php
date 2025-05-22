<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use App\Mail\SendPoSMail;
use App\Mail\FeedbackMail;
use App\Mail\StaffActionMail;
use App\Mail\StaffActionPoRMail;
use App\Mail\StaffActionPoSMail;
use Carbon\Carbon;
use PDO;
use DateTime;


class OldFeedbackController extends Controller
{
    public function index()
    {
        ini_set('memory_limit', '8192M');

        // $day = now()->day;
        $day = '16';

        $query = DB::connection('BTID')
        ->table('mgr.cb_cash_request_appr')
        ->where('mgr.cb_cash_request_appr.status', '=', 'A')
        ->whereDay('mgr.cb_cash_request_appr.approved_date', '=', $day)
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
            $entity_cd = $data->entity_cd;
            $exploded_values = explode(" ", $entity_cd);
            $project_no = implode('', $exploded_values) . '01';
            $doc_no = $data->doc_no;
            $trx_type = $data->trx_type;
            $level_no = $data->level_no;
            $user_id = $data->user_id;
            $type = $data->TYPE;
            $module = $data->module;
            $status = $data->status;
            $ref_no = $data->ref_no;
            $doc_date = $data->doc_date;
            $descs = '(APPROVED)';
            $dateTime = new DateTime($doc_date);
            $supervisor = 'Y';
            $reason = '0';
            if ($type == 'E' && $module == "CB")
            {
                $descsLong = 'Propose Transfer to Bank';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_fupd';
                $folder = 'feedbackCbFupd';
            } else if ($type == 'U' && $module == "CB")
            {
                $descsLong = 'Payment Request';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_ppu';
                $folder = 'feedbackCb';
            } else if ($type == 'V' && $module == "CB")
            {
                $descsLong = 'Payment Request';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_ppu_vvip';
                $folder = 'feedbackCb';
            } else if ($type == 'D' && $module == "CB")
            {
                $descsLong = 'Recapitulation Bank';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_rpb';
                $folder = 'feedbackCb';
            } else if ($type == 'D' && $module == "CB")
            {
                $descsLong = 'Cash Advance Settlement';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_cb_rum';
                $folder = 'feedbackCb';
            } else if ($type == 'A' && $module == "PO")
            {
                $descsLong = 'Purchase Order';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_po_order';
                $folder = 'feedbackPoOrder';
            } else if ($type == 'Q' && $module == "PO")
            {
                $descsLong = 'Purchase Requisition';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_po_request';
                $folder = 'feedbackPOR';
            } else if ($type == 'S' && $module == "PO")
            {
                $descsLong = 'Purchase Selection';
                $cacheFile = 'email_feedback_sent_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $exec = 'mgr.x_send_mail_approval_feedback_po_selection';
                $folder = 'feedbackPOS';
            }
            $defaultDate = date('Ym') . $day;
            $cacheFilePath = storage_path('app/mail_cache/'.$folder.'/' . $defaultDate . '/' . $cacheFile);
            $cacheDirectory = dirname($cacheFilePath);
                
            // Ensure the directory exists
            if (!file_exists($cacheDirectory)) {
                mkdir($cacheDirectory, 0755, true);
            }
        
            if (!file_exists($cacheFilePath)) {
                $pdo = DB::connection('BTID')->getPdo();
                $sth = $pdo->prepare("SET NOCOUNT ON; EXEC ".$exec." ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                $sth->bindParam(1, $entity_cd);
                $sth->bindParam(2, $doc_no);
                $sth->bindParam(3, $level_no);
                $sth->bindParam(4, $status);
                $sth->bindParam(5, $type);
                $sth->bindParam(6, $module);
                $sth->bindParam(7, $descs);
                $sth->bindParam(8, $descsLong);
                $sth->bindParam(9, $reason);
                $sth->execute();
            }
        }
    }
}
