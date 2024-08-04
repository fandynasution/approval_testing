<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use App\Mail\FeedbackMail;
use App\Mail\StaffActionPoOrderMail;
use App\Mail\StaffActionCbMail;
use App\Mail\StaffActionCbFupdMail;
use Carbon\Carbon;

class StaffFeedbackController extends Controller
{
    public function feedback_po(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
            $action = 'Approval';
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
        }

        $list_of_urls = explode('; ', $request->url_file);
        $list_of_files = explode('; ', $request->file_name);
        $list_of_doc = explode('; ', $request->document_link);

        $url_data = [];
        $file_data = [];
        $doc_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }
        foreach ($list_of_doc as $doc) {
            $doc_data[] = $doc;
        }

        $EmailBack = array(
            'doc_no'            => $request->doc_no,
            'action'            => $action,
            'reason'            => $request->reason,
            'descs'             => $request->descs,
            'subject'		    => $request->subject,
            'bodyEMail'		    => $bodyEMail,
            'user_name'         => $request->user_name,
            'staff_act_send'    => $request->staff_act_send,
            'entity_name'       => $request->entity_name,
            'entity_cd'         => $request->entity_cd,
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'doc_link'          => $doc_data,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );
        $emailAddresses = strtolower($request->email_addr);
        $doc_no = $request->doc_no;
        $entity_name = $request->entity_name;
        $entity_cd = $request->entity_cd;
        $status = $request->status;
        $approve_seq = $request->approve_seq;
        try {
            $emailAddresses = strtolower($request->email_addr);
            $doc_no = $request->doc_no;
            $entity_name = $request->entity_name;
            $entity_cd = $request->entity_cd;
            $status = $request->status;
            $approve_seq = $request->approve_seq;
            if (!empty($emailAddresses)) {
                $emails = $emailAddresses;

                $emailSent = false;
                // Check if the email has been sent before for this document
                $cacheFile = 'email_feedback_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/feedbackPoOrder/' . date('Ymd'). '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
            
                // Ensure the directory exists
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }

                // Acquire an exclusive lock
                $lockFile = $cacheFilePath . '.lock';
                $lockHandle = fopen($lockFile, 'w');
                if (!flock($lockHandle, LOCK_EX)) {
                    // Failed to acquire lock, handle appropriately
                    fclose($lockHandle);
                    throw new Exception('Failed to acquire lock');
                }
        
                if (!file_exists($cacheFilePath)) {
                    // Send email
                    Mail::to($emails)->send(new StaffActionPoOrderMail($EmailBack));
            
                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');
                    $sentTo = $emailAddresses;
                    Log::channel('sendmailfeedback')->info('Email Feedback doc_no '.$doc_no.' Entity ' . $entity_cd.' berhasil dikirim ke: ' . $sentTo);
                    return 'Email berhasil dikirim ke: ' . $sentTo;
                    $emailSent = true;
                }
            } else {
                Log::channel('sendmail')->warning("Tidak ada alamat email untuk feedback yang diberikan");
                Log::channel('sendmail')->warning($doc_no);
                return "Tidak ada alamat email untuk feedback yang diberikan";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email. Cek log untuk detailnya.";
        }      
    }

    public function feedback_cb_fupd(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
            $action = 'Approval';
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
        }

        $list_of_urls = explode('; ', $request->url_file);
        $list_of_files = explode('; ', $request->file_name);

        $url_data = [];
        $file_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        $EmailBack = array(
            'doc_no'            => $request->doc_no,
            'action'            => $action,
            'reason'            => $request->reason,
            'descs'             => $request->descs,
            'subject'		    => $request->subject,
            'bodyEMail'		    => $bodyEMail,
            'user_name'         => $request->user_name,
            'staff_act_send'    => $request->staff_act_send,
            'entity_name'       => $request->entity_name,
            'entity_cd'         => $request->entity_cd,
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );
        $emailAddresses = strtolower($request->email_addr);
        $doc_no = $request->doc_no;
        $entity_name = $request->entity_name;
        $entity_cd = $request->entity_cd;
        $status = $request->status;
        $approve_seq = $request->approve_seq;
        try {
            $emailAddresses = strtolower($request->email_addr);
            $doc_no = $request->doc_no;
            $entity_name = $request->entity_name;
            $entity_cd = $request->entity_cd;
            $status = $request->status;
            $approve_seq = $request->approve_seq;
            if (!empty($emailAddresses)) {
                $emails = $emailAddresses;

                $emailSent = false;
                
                // Check if the email has been sent before for this document
                $cacheFile = 'email_feedback_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/feedbackCbFupd/' . date('Ymd'). '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
            
                // Ensure the directory exists
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }

                // Acquire an exclusive lock
                $lockFile = $cacheFilePath . '.lock';
                $lockHandle = fopen($lockFile, 'w');
                if (!flock($lockHandle, LOCK_EX)) {
                    // Failed to acquire lock, handle appropriately
                    fclose($lockHandle);
                    throw new Exception('Failed to acquire lock');
                }
        
                if (!file_exists($cacheFilePath)) {
                    // Send email
                    Mail::to($emails)->send(new StaffActionCbFupdMail($EmailBack));
            
                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');
                    $sentTo = $emailAddresses;
                    Log::channel('sendmailfeedback')->info('Email Feedback doc_no '.$doc_no.' Entity ' . $entity_cd.' berhasil dikirim ke: ' . $sentTo);
                    return 'Email berhasil dikirim ke: ' . $sentTo;
                    $emailSent = true;
                }
            } else {
                Log::channel('sendmail')->warning("Tidak ada alamat email untuk feedback yang diberikan");
                Log::channel('sendmail')->warning($doc_no);
                return "Tidak ada alamat email untuk feedback yang diberikan";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email. Cek log untuk detailnya.";
        }      
    }

    public function feedback_cb(Request $request)
    {
        $callback = array(
            'Error' => false,
            'Pesan' => '',
            'Status' => 200
        );

        $action = ''; // Initialize $action
        $bodyEMail = '';

        if (strcasecmp($request->status, 'R') == 0) {

            $action = 'Revision';
            $bodyEMail = 'Please revise '.$request->descs.' No. '.$request->doc_no.' with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'C') == 0){
            
            $action = 'Cancellation';
            $bodyEMail = $request->descs.' No. '.$request->doc_no.' has been cancelled with the reason : '.$request->reason;

        } else if (strcasecmp($request->status, 'A') == 0) {
            $action = 'Approval';
            $bodyEMail = 'Your Request '.$request->descs.' No. '.$request->doc_no.' has been Approved';
        }

        $list_of_urls = explode('; ', $request->url_file);
        $list_of_files = explode('; ', $request->file_name);

        $url_data = [];
        $file_data = [];

        foreach ($list_of_urls as $url) {
            $url_data[] = $url;
        }

        foreach ($list_of_files as $file) {
            $file_data[] = $file;
        }

        $EmailBack = array(
            'doc_no'            => $request->doc_no,
            'action'            => $action,
            'reason'            => $request->reason,
            'descs'             => $request->descs,
            'subject'		    => $request->subject,
            'bodyEMail'		    => $bodyEMail,
            'user_name'         => $request->user_name,
            'staff_act_send'    => $request->staff_act_send,
            'entity_name'       => $request->entity_name,
            'entity_cd'         => $request->entity_cd,
            'url_file'          => $url_data,
            'file_name'         => $file_data,
            'action_date'       => Carbon::now('Asia/Jakarta')->format('d-m-Y H:i')
        );
        $emailAddresses = strtolower($request->email_addr);
        $doc_no = $request->doc_no;
        $entity_name = $request->entity_name;
        $entity_cd = $request->entity_cd;
        $status = $request->status;
        $approve_seq = $request->approve_seq;
        try {
            $emailAddresses = strtolower($request->email_addr);
            $doc_no = $request->doc_no;
            $entity_name = $request->entity_name;
            $entity_cd = $request->entity_cd;
            $status = $request->status;
            $approve_seq = $request->approve_seq;
            if (!empty($emailAddresses)) {
                $emails = $emailAddresses;

                $emailSent = false;
                
                // Check if the email has been sent before for this document
                $cacheFile = 'email_feedback_sent_' . $approve_seq . '_' . $entity_cd . '_' . $doc_no . '_' . $status . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/feedbackCb/' . date('Ymd'). '/' . $cacheFile);
                $cacheDirectory = dirname($cacheFilePath);
            
                // Ensure the directory exists
                if (!file_exists($cacheDirectory)) {
                    mkdir($cacheDirectory, 0755, true);
                }

                // Acquire an exclusive lock
                $lockFile = $cacheFilePath . '.lock';
                $lockHandle = fopen($lockFile, 'w');
                if (!flock($lockHandle, LOCK_EX)) {
                    // Failed to acquire lock, handle appropriately
                    fclose($lockHandle);
                    throw new Exception('Failed to acquire lock');
                }
        
                if (!file_exists($cacheFilePath)) {
                    // Send email
                    Mail::to($emails)->send(new StaffActionCbMail($EmailBack));
            
                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');
                    $sentTo = $emailAddresses;
                    Log::channel('sendmailfeedback')->info('Email Feedback doc_no '.$doc_no.' Entity ' . $entity_cd.' berhasil dikirim ke: ' . $sentTo);
                    return 'Email berhasil dikirim ke: ' . $sentTo;
                    $emailSent = true;
                }
            } else {
                Log::channel('sendmail')->warning("Tidak ada alamat email untuk feedback yang diberikan");
                Log::channel('sendmail')->warning($doc_no);
                return "Tidak ada alamat email untuk feedback yang diberikan";
            }
        } catch (\Exception $e) {
            Log::channel('sendmail')->error('Gagal mengirim email: ' . $e->getMessage());
            return "Gagal mengirim email. Cek log untuk detailnya.";
        }      
    }
}