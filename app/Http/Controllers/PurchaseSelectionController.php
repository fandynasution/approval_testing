<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Mail\SendPoSMail;
use PDO;
use DateTime;

class PurchaseSelectionController extends Controller
{
    public function Mail(Request $request)
    {
        if (strpos($request->po_descs, "\n") !== false) {
            $po_descs = str_replace("\n", ' (', $request->po_descs) . ')';
        } else {
            $po_descs = $request->po_descs;
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

        $list_of_approve = explode('; ',  $request->approve_exist);
        $approve_data = [];
        foreach ($list_of_approve as $approve) {
            $approve_data[] = $approve;
        }

        $total_amt = number_format($request->total_amt, 2, '.', ',');
        
        $dataArray = array(
            'ref_no'        => $request->ref_no,
            'po_doc_no'     => $request->po_doc_no,
            'po_descs'      => $po_descs,
            'supplier_name' => $request->supplier_name,
            'sender'        => $request->sender,
            'sender_addr'   => $request->sender_addr,
            'entity_name'   => $request->entity_name,
            'descs'         => $request->descs,
            'user_name'     => $request->user_name,
            'url_file'      => $url_data,
            'file_name'     => $file_data,
            'approve_list'  => $approve_data,
            'curr_cd'       => $request->curr_cd,
            'total_amt'     => $total_amt,
            'clarify_user'  => $request->clarify_user,
            'clarify_email' => $request->clarify_email,
            'body'          => "Please approve Quotation No. ".$request->po_doc_no." for ".$po_descs,
            'subject'       => "Need Approval for Quotation No.  ".$request->po_doc_no,
        );

        $data2Encrypt = array(
            'entity_cd'     => $request->entity_cd,
            'project_no'    => $request->project_no,
            'doc_no'        => $request->doc_no,
            'request_no'    => $request->request_no,
            'trx_date'      => $request->trx_date,
            'level_no'      => $request->level_no,
            'usergroup'     => $request->usergroup,
            'user_id'       => $request->user_id,
            'supervisor'    => $request->supervisor,
            'type'          => 'S',
            'type_module'   => 'PO',
            'text'          => 'Purchase Selection'
        );

        $encryptedData = Crypt::encrypt($data2Encrypt);
    
        try {
            $emailAddress = strtolower($request->email_addr);
            $approveSeq = $request->approve_seq;
            $entityCd = $request->entity_cd;
            $docNo = $request->doc_no;
            $levelNo = $request->level_no;
        
            // Check if email address is provided and not empty
            if (!empty($emailAddress)) {
                // Check if the email has been sent before for this document
                $cacheFile = 'email_sent_' . $approveSeq . '_' . $entityCd . '_' . $docNo . '_' . $levelNo . '.txt';
                $cacheFilePath = storage_path('app/mail_cache/send_pos/' . date('Ymd') . '/' . $cacheFile);
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
                    throw new Exception('Failed to acquire lock for sending email');
                }
        
                if (!file_exists($cacheFilePath) || (file_exists($cacheFilePath) && !strpos(file_get_contents($cacheFilePath), 'sent'))) {
                    // Send email
                    Mail::to($emailAddress)->send(new SendPoSMail($encryptedData, $dataArray));
        
                    // Mark email as sent
                    file_put_contents($cacheFilePath, 'sent');
        
                    // Log the success
                    Log::channel('sendmailapproval')->info('Email Purchase Selection doc_no '.$docNo.' Entity ' . $entityCd.' berhasil dikirim ke: ' . $emailAddress);
                    return 'Email berhasil dikirim ke: ' . $emailAddress;
                } else {
                    // Email was already sent
                    Log::channel('sendmailapproval')->info('Email Purchase Selection doc_no '.$docNo.' Entity ' . $entityCd.' already sent to: ' . $emailAddress);
                    return 'Email has already been sent to: ' . $emailAddress;
                }
            } else {
                // No email address provided
                Log::channel('sendmail')->warning("No email address provided for document " . $docNo);
                return "No email address provided";
            }
        } catch (\Exception $e) {
            // Error occurred
            Log::channel('sendmail')->error('Failed to send email: ' . $e->getMessage());
            return "Failed to send email: " . $e->getMessage();
        }
        
    }

    public function processData($status = '', $encrypt = '')
    {
        Artisan::call('config:cache');
        Artisan::call('cache:clear');
        Cache::flush();
        $cacheKey = 'processData_' . $encrypt;

        // Check if the data is already cached
        if (Cache::has($cacheKey)) {
            // If cached data exists, clear it
            Cache::forget($cacheKey);
        }

        DB::connection('BTID')->enableQueryLog();

        Log::info('Starting database query execution for processData');
        $data = Crypt::decrypt($encrypt);

        $msg = " ";
        $notif = " ";
        $st = " ";
        $image = " ";

        Log::info('Decrypted data: ' . json_encode($data));

        $where = array(
            'doc_no'        => $data["doc_no"],
            'entity_cd'     => $data["entity_cd"],
            'level_no'      => $data["level_no"],
            'type'          => $data["type"],
            'module'        => $data["type_module"],
        );

        $query = DB::connection('BTID')
            ->table('mgr.cb_cash_request_appr')
            ->where($where)
            ->whereIn('status', array("A", "R", "C"))
            ->get();

        $queryLog = DB::connection('BTID')->getQueryLog();

        Log::info('Executed query: ' . json_encode($queryLog));

        Log::info('First query result: ' . json_encode($query));

        if (count($query) > 0) {
            $msg = 'You Have Already Made a Request to ' . $data["text"] . ' No. ' . $data["doc_no"];
            $notif = 'Restricted !';
            $st  = 'OK';
            $image = "double_approve.png";
            $msg1 = array(
                "Pesan" => $msg,
                "St" => $st,
                "notif" => $notif,
                "image" => $image
            );
            return view("email.after", $msg1);
        } else {
            $where2 = array(
                'doc_no'        => $data["doc_no"],
                'status'        => 'P',
                'entity_cd'     => $data["entity_cd"],
                'level_no'      => $data["level_no"],
                'type'          => $data["type"],
                'module'        => $data["type_module"],
            );

            $query2 = DB::connection('BTID')
                ->table('mgr.cb_cash_request_appr')
                ->where($where2)
                ->get();

            $queryLog2 = DB::connection('BTID')->getQueryLog();

            Log::info('Executed query: ' . json_encode($queryLog2));

            Log::info('Second query result: ' . json_encode($query2));

            if (count($query2) == 0) {
                $msg = 'There is no ' . $data["text"] . ' with No. ' . $data["doc_no"];
                $notif = 'Restricted !';
                $st  = 'OK';
                $image = "double_approve.png";
                $msg1 = array(
                    "Pesan" => $msg,
                    "St" => $st,
                    "notif" => $notif,
                    "image" => $image
                );
                return view("email.after", $msg1);
            } else {
                $name   = " ";
                $bgcolor = " ";
                $valuebt  = " ";
                if ($status == 'A') {
                    $name   = 'Approval';
                    $bgcolor = '#40de1d';
                    $valuebt  = 'Approve';
                } else if ($status == 'R') {
                    $name   = 'Revision';
                    $bgcolor = '#f4bd0e';
                    $valuebt  = 'Revise';
                } else {
                    $name   = 'Cancellation';
                    $bgcolor = '#e85347';
                    $valuebt  = 'Cancel';
                }
                $dataArray = Crypt::decrypt($encrypt);
                $data = array(
                    "status"    => $status,
                    "encrypt"   => $encrypt,
                    "name"      => $name,
                    "bgcolor"   => $bgcolor,
                    "valuebt"   => $valuebt
                );
                return view('email/pos/passcheckwithremark2', $data);
                Artisan::call('config:cache');
                Artisan::call('cache:clear');
            }
        }
    }

    public function getaccess(Request $request)
    {
        $data = Crypt::decrypt($request->encrypt);

        $trx_date = rtrim($data["trx_date"], '.');

        // Print the cleaned date string to verify its format

        // First, parse the date using the correct format
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $trx_date);

        $dateTime = $dateTime->format('d-m-Y');

        $status = $request->status;

        $reasonget = $request->reason;

        $descstatus = " ";
        $imagestatus = " ";

        $msg = " ";
        $msg1 = " ";
        $notif = " ";
        $st = " ";
        $image = " ";

        if ($reasonget == '' || $reasonget == NULL) {
            $reason = '0';
        } else {
            $reason = $reasonget;
        }

        if ($status == "A") {
            $descstatus = "Approved";
            $imagestatus = "approved.png";
        } else if ($status == "R") {
            $descstatus = "Revised";
            $imagestatus = "revise.png";
        } else {
            $descstatus = "Cancelled";
            $imagestatus = "reject.png";
        }
        if ($reason=''||$reason=null||$reason=NULL||$reason='null'||$reason='NULL') {
            $reason='0';
        }
        $pdo = DB::connection('BTID')->getPdo();
        $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_selection ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
        $sth->bindParam(1, $data["entity_cd"]);
        $sth->bindParam(2, $data["project_no"]);
        $sth->bindParam(3, $data["doc_no"]);
        $sth->bindParam(4, $data["request_no"]);
        $sth->bindParam(5, $dateTime);
        $sth->bindParam(6, $status);
        $sth->bindParam(7, $data["level_no"]);
        $sth->bindParam(8, $data["usergroup"]);
        $sth->bindParam(9, $data["user_id"]);
        $sth->bindParam(10, $data["supervisor"]);
        $sth->bindParam(11, $reason);
        $sth->execute();
        if ($sth == true) {
            $msg = "You Have Successfully ".$descstatus." the Purchase Selection No. ".$data["doc_no"];
            $notif = $descstatus." !";
            $st = 'OK';
            $image = $imagestatus;
        } else {
            $msg = "You Failed to ".$descstatus." the Purchase Selection No.".$data["doc_no"];
            $notif = 'Fail to '.$descstatus.' !';
            $st = 'OK';
            $image = "reject.png";
        }
        $msg1 = array(
            "Pesan" => $msg,
            "St" => $st,
            "notif" => $notif,
            "image" => $image
        );
        return view("email.after", $msg1);
    }
}
