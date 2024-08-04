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
use App\Mail\SendPoSMail;
use App\Mail\FeedbackMail;
use App\Mail\StaffActionMail;
use App\Mail\StaffActionPoRMail;
use App\Mail\StaffActionPoSMail;
use Carbon\Carbon;
use PDO;
use DateTime;


class AutoSendTestController extends Controller
{
    public function index()
    {
        $query = DB::connection('BTID')
            ->table('mgr.cb_cash_request_appr')
            ->whereNotNull('sent_mail')
            ->where('status', 'P')
            ->orderBy('entity_cd', 'desc')
            ->orderBy('doc_no', 'desc')
            ->get();

        $count = $query->count();

        var_dump($count);

        foreach ($query as $data)
        {
            $entity_cd = $data->entity_cd;
            $exploded_values = explode(" ", $entity_cd);
            $project_no = implode('', $exploded_values) . '01';
            $doc_no = $data->doc_no;
            $trx_type = $data->trx_type;
            $level_no = $data->level_no;
            $user_id = $data->user_id;
            $type = $data->TYPE;
            $module = $data->module;
            $ref_no = $data->ref_no;
            $doc_date = $data->doc_date;
            $dateTime = new DateTime($doc_date);
            $supervisor = 'Y';
            $reason = '0';

            if ($type == 'U' && $module == "CB") {
                $exec = 'mgr.x_send_mail_approval_cb_ppu';
            } else if ($type == 'V' && $module == "CB"){
                $exec = 'mgr.x_send_mail_approval_cb_ppu_vvip';
            } else if ($type == 'D' && $module == "CB") {
                $exec = 'mgr.x_send_mail_approval_cb_rpb';
            } else if ($type == 'G' && $module == "CB") {
                $exec = 'mgr.x_send_mail_approval_cb_rum';
            } else if ($type == 'E' && $module == "CM") {
                $exec = 'mgr.xrl_send_mail_approval_cm_contract_entry';
            } else if ($type == 'A' && $module == "CM") {
                $exec = 'mgr.xrl_send_mail_approval_cm_progress';
            } else if ($type == 'B' && $module == "PL") {
                $exec = 'mgr.xrl_send_mail_approval_pl_budget_lyman';
            } else if ($type == 'R' && $module == "PL") {
                $exec = 'mgr.xrl_send_mail_approval_pl_budget_revision';
            } else if ($type == 'A' && $module == "PO") {
                $exec = 'mgr.x_send_mail_approval_po_order';
            } else if ($type == 'E' && $module == "CM") {
                $exec = 'mgr.xrl_send_mail_approval_cm_contract_entry';
            } else if ($type == 'C' && $module == "CM") {
                $exec = 'mgr.xrl_send_mail_approval_cm_contractclose';
            } else if ($type == 'B' && $module == "CM") {
                $exec = 'mgr.xrl_send_mail_approval_cm_contractdone';
            } else if ($type == 'A' && $module == "CM") {
                $exec = 'mgr.xrl_send_mail_approval_cm_progress';
            } else if ($type == 'D' && $module == "CM") {
                $exec = 'mgr.xrl_send_mail_approval_cm_varianorder';
            }

            $whereUg = array(
                'user_name' => $user_id
            );

            $queryUg = DB::connection('BTID')
            ->table('mgr.security_groupings')
            ->where($whereUg)
            ->get();

            $user_group = $queryUg[0]->group_name;

            if ($level_no == 1) {
                $status = 'P';
                $downLevel = '0';
            } else {
                $status = 'A';
                $downLevel  = $level_no - 1;
            }
            $date = date('d-m-Y', strtotime($doc_date));

            if ($type == 'S' && $module == "PO") {
                $date = date('d-m-Y', strtotime($doc_date));
                $pdo = DB::connection('BTID')->getPdo();
                $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_selection ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                $sth->bindParam(1, $entity_cd);
                $sth->bindParam(2, $project_no);
                $sth->bindParam(3, $doc_no);
                $sth->bindParam(4, $ref_no);
                $sth->bindParam(5, $date);
                $sth->bindParam(6, $status);
                $sth->bindParam(7, $downLevel);
                $sth->bindParam(8, $user_group);
                $sth->bindParam(9, $user_id);
                $sth->bindParam(10, $supervisor);
                $sth->bindParam(11, $reason);

                var_dump("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_selection ".$entity_cd.", ".$project_no.",".$doc_no.",".$ref_no.",".$date.",".$status.",".$downLevel.",".$user_group.",".$user_id.",".$supervisor.",".$reason);
            } else if ($type == 'Q' && $module == "PO") {
                $pdo = DB::connection('BTID')->getPdo();
                $sth = $pdo->prepare("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_request ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                $sth->bindParam(1, $entity_cd);
                $sth->bindParam(2, $project_no);
                $sth->bindParam(3, $doc_no);
                $sth->bindParam(4, $status);
                $sth->bindParam(5, $downLevel);
                $sth->bindParam(6, $user_group);
                $sth->bindParam(7, $user_id);
                $sth->bindParam(8, $supervisor);
                $sth->bindParam(9, $reason);
                var_dump("SET NOCOUNT ON; EXEC mgr.x_send_mail_approval_po_request ".$entity_cd.", ".$project_no.",".$doc_no.",".$status.",".$downLevel.",".$user_group.",".$user_id.",".$supervisor.",".$reason);
            } else {
                if ($module == 'CM') {
                    $pdo = DB::connection('BTID')->getPdo();
                    $sth = $pdo->prepare("SET NOCOUNT ON; EXEC ".$exec." ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                    $sth->bindParam(1, $entity_cd);
                    $sth->bindParam(2, $project_no);
                    $sth->bindParam(3, $doc_no);
                    $sth->bindParam(4, $ref_no);
                    $sth->bindParam(5, $status);
                    $sth->bindParam(6, $downLevel);
                    $sth->bindParam(7, $user_group);
                    $sth->bindParam(8, $user_id);
                    $sth->bindParam(9, $supervisor);
                    $sth->bindParam(10, $reason);
                    var_dump("SET NOCOUNT ON; EXEC ".$exec." ".$entity_cd.", ".$project_no.",".$doc_no.",".$ref_no.",".$status.",".$downLevel.",".$user_group.",".$user_id.",".$supervisor.",".$reason);
                } else {
                    $pdo = DB::connection('BTID')->getPdo();
                    $sth = $pdo->prepare("SET NOCOUNT ON; EXEC ".$exec." ?, ?, ?, ?, ?, ?, ?, ?, ?, ?;");
                    $sth->bindParam(1, $entity_cd);
                    $sth->bindParam(2, $project_no);
                    $sth->bindParam(3, $doc_no);
                    $sth->bindParam(4, $trx_type);
                    $sth->bindParam(5, $status);
                    $sth->bindParam(6, $downLevel);
                    $sth->bindParam(7, $user_group);
                    $sth->bindParam(8, $user_id);
                    $sth->bindParam(9, $supervisor);
                    $sth->bindParam(10, $reason);
                    var_dump("SET NOCOUNT ON; EXEC ".$exec." ".$entity_cd.", ".$project_no.",".$doc_no.",".$trx_type.",".$status.",".$downLevel.",".$user_group.",".$user_id.",".$supervisor.",".$reason);
                }
            }
        }
    }
}