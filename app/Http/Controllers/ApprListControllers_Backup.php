<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PDO;

class ApprListControllers extends Controller
{
    public function index()
    {
        return view('apprlist.index'); // Pastikan file view ini ada di resources/views/apprlist/index.blade.php
    }

    public function getData()
    {
        $query = DB::connection('BTID')
            ->table('mgr.cb_cash_request_appr')
            ->where('status', 'P')
            ->whereNotNull('currency_cd')
            ->whereNotNull('sent_mail_date')
            ->whereRaw("LTRIM(RTRIM(entity_cd)) NOT LIKE '%[^0-9]%'")
            ->where('sent_mail_date', '<=', DB::raw("DATEADD(DAY, 1, GETDATE())")) // Hingga akhir hari ini
            ->where('audit_date', '>=', DB::raw("CONVERT(datetime, '2024-03-28', 120)"));

        return DataTables::of($query)->make(true);
    }
    
    public function sendData(Request $request)
    {
        // Log::channel('resend')->info('Data dikirim: ' . json_encode($request->all()));

        // return response()->json(['message' => 'Data diterima']);
        $entity_cd = $request->input('entity_cd');
        $doc_no = $request->input('doc_no');
        $user_id = $request->input('user_id');
        $level_no = $request->input('level_no');
        $approve_seq = $request->input('approve_seq');

        \Log::channel('resend')->info("Received Data: ", compact('entity_cd', 'doc_no', 'user_id'));

        // Menggunakan satu koneksi untuk mengurangi overhead
        $db = DB::connection('BTID');

        // Mengambil semua data yang dibutuhkan dalam satu query
        $query = $db->table('mgr.cb_cash_request_appr')
            ->where(compact('entity_cd', 'doc_no', 'user_id'))
            ->first();

        $query_project = $db->table('mgr.pl_project')
            ->where('entity_cd', $entity_cd)
            ->first(['project_no']);

        $queryGroup = $db->table('mgr.security_groupings')
            ->where('user_name', $user_id)
            ->first(['group_name']);

        $queryUser = $db->table('mgr.security_users')
            ->where('name', $user_id)
            ->first(['supervisor']);

        // Assign nilai dari hasil query
        $project_no = optional($query_project)->project_no;
        $trx_type = optional($query)->trx_type;
        $type = optional($query)->TYPE;
        $module = optional($query)->module;
        $status = optional($query)->status;
        $level_no = optional($query)->level_no;
        if ($level_no == 1) {
            $statussend = 'P';
            $downLevel = '0';
        } elseif ($level_no > 1) {
            $downLevel  = $level_no - 1;
            $statussend = 'A';
        }
        $user_group = optional($queryGroup)->group_name;
        $spv = optional($queryUser)->supervisor;
        $ref_no = optional($query)->ref_no;
        $trx_date = optional($query)->doc_date ? Carbon::parse($query->doc_date)->format('d-m-Y') : null;
        $reason = '0';

        $executeProcedure = function ($procedure, $params) use ($db) {
            $pdo = $db->getPdo();
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $query = "SET NOCOUNT ON; EXEC $procedure $placeholders;";
        
            // Logging SQL Query sebelum eksekusi
            \Log::info("Executing Procedure: $query", ['parameters' => $params]);
        
            $sth = $pdo->prepare($query);
            foreach ($params as $index => $param) {
                $sth->bindValue($index + 1, $param);
            }
        
            $result = $sth->execute();
        
            // Logging hasil eksekusi
            if ($result) {
                \Log::info("Procedure executed successfully: $procedure", ['parameters' => $params]);
                return response()->json(['message' => 'SUCCESS'], 200);
            } else {
                \Log::error("Procedure execution failed: $procedure", ['parameters' => $params]);
                return response()->json(['message' => 'FAILED'], 400);
            }
        };
        $firstdir = '/var/www/html/btid_trial/storage/app/mail_cache';
        $date = date('Ymd'); // Mendapatkan tanggal hari ini dalam format yyyymmdd        

        // Menentukan prosedur yang akan dijalankan
        if ($module === 'PO') {
            if ($type === 'Q') {
                $directory = "send_porequeset/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_po_request', [
                    $entity_cd, $project_no, $doc_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'S') {
                $directory = "send_pos/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_po_selection', [
                    $entity_cd, $project_no, $doc_no, $ref_no, $trx_date, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'A') {
                $directory = "send_porder/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_po_order', [
                    $entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            }
        } elseif ($module === 'CB') {
            if ($type === 'D') {
                $directory = "send_cbrpb/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_cb_rpb', [
                    $entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'E') {
                $directory = "send_cbfupd/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_cb_fupd', [
                    $entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'G') {
                $directory = "send_cbrum/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_cb_rum', [
                    $entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'U') {
                $directory = "send_cbppu/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_cb_ppu', [
                    $entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'V') {
                $directory = "send_cbppuvvip/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.x_send_mail_approval_cb_ppu_vvip', [
                    $entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            }
        } elseif ($module === 'CM') {
            if ($type === 'A') {
                $directory = "send_cmprogress/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_cm_progress', [
                    $entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'B') {
                $directory = "send_cmdone/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_cm_contractdone', [
                    $entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'C') {
                $directory = "send_cmclose/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_cm_contractclose', [
                    $entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'D') {
                $directory = "send_varianorder/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_cm_varianorder', [
                    $entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            } elseif ($type === 'E') {
                $directory = "send_cmentry/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_cm_contract_entry', [
                    $entity_cd, $project_no, $doc_no, $ref_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            }
        } elseif ($module === 'PL') {
            if ($type === 'Y' && $trx_type === 'RB') {
                $directory = "send_PlBudgetRevision/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_pl_budget_revision', [
                    $entity_cd, $project_no, $doc_no, $trx_type, $statussend, $downLevel, $user_id
                ]);
            } elseif ($type === 'Y') {
                $directory = "send_PlBudget/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_pl_budget_lyman', [
                    $entity_cd, $project_no, $doc_no, $statussend, $downLevel, $user_id
                ]);
            }
        } elseif ($module === 'TM') {
            if ($type === 'R') {
                $queryrenewno = $db->table('mgr.pm_tenancy_renew')
                ->where('entity_cd', $entity_cd)
                ->where('project_no', $project_no)
                ->where('tenant_no', $ref_no)
                ->first(['renew_no']);
                $renew_no = optional($queryrenewno)->renew_no;
                $directory = "send_contract_renew/$date"; 
                $pattern = sprintf("email_sent_%s_%s_%s_%s.txt", $approve_seq, $entity_cd, $doc_no, $level_no);
                $filePath = "$firstdir/$directory/$pattern";
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                return $executeProcedure('mgr.xrl_send_mail_approval_tm_contractrenew', [
                    $entity_cd, $project_no, $doc_no, $ref_no, $renew_no, $statussend, $downLevel, $user_group, $user_id, $spv, $reason
                ]);
            }
        }
        return response()->json(['message' => 'INVALID REQUEST'], 400);
    }
}
