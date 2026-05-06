<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport">
        <meta name="viewport" content="width=device-width">

        <title>BTID - Approval Lists</title>

        <link rel="stylesheet" href="assets/css/siqtheme.css">

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

        <link rel="shortcut icon" type="image/x-icon" href="{{ url('public/images/KuraKuraBali-iconew.ico') }}">

        <!-- jQuery (Pastikan jQuery disertakan) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    </head>
    <body class="theme-dark" style="overflow: auto;" cz-shortcut-listen="true">
        <div class="grid-wrapper sidebar-bg bg1">
            <div class="main">
                <div class='row'>
                    <div class="col-lg-12">
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="caption uppercase">
                                    Approval List
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover init-datatable dataTable no-footer" id="DataTables_Table_0" role="grid" aria-describedby="DataTables_Table_0_info">
                                    <thead class="thead-light">
                                        <tr role="row">
                                            <th>Entity Code</th>
                                            <th>Document No</th>
                                            <th>Approved Sequence</th>
                                            <th>User ID</th>
                                            <th>Level No</th>
                                            <th>Status</th>
                                            <th>Sent Mail Date</th>
                                            <th>Module</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; display: flex; justify-content: center; align-items: center; color: white; font-size: 20px;">
            <span>Processing...</span>
        </div>
        <script>
            $(document).ready(function() {
                $('#loading-overlay').hide();
                $('#DataTables_Table_0').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('apprlist.getData') }}", // Pastikan route ini sesuai
                    order: [[6, 'desc']],
                    columns: [
                        { data: 'entity_cd', name: 'entity_cd' },
                        { data: 'doc_no', name: 'doc_no' },
                        { data: 'approve_seq', name: 'approve_seq', visible: false },
                        { data: 'user_id', name: 'user_id' },
                        { data: 'level_no', name: 'level_no' },
                        { data: 'status', name: 'status' },
                        { data: 'sent_mail_date', name: 'sent_mail_date' },
                        {
                            data: null,
                            name: 'cetak_option',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                let options = {
                                    'PO-S': 'Quotation',
                                    'PO-Q': 'Purchase Requisition',
                                    'PO-A': 'Purchase Order',
                                    'CB-D': 'Recapitulation Bank',
                                    'CB-E': 'Propose Transfer',
                                    'CB-G': 'Cash Advance Settlement',
                                    'CB-U': 'Payment Request',
                                    'CB-V': 'Payment Request VVIP',
                                    'CM-A': 'Contract Progress',
                                    'CM-B': 'Contract Complete',
                                    'CM-C': 'Warranty Complete',
                                    'CM-D': 'Varian Order',
                                    'CM-E': 'Contract Entry',
                                    'PL-Y': 'PL Budget',
                                    'TM-R': 'Contract Renew'
                                };
                                return options[`${row.module}-${row.TYPE}`] || '';
                            }
                        },
                        {
                            data: null,
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return `<button class="btn btn-primary send-data" 
                                            data-entity_cd="${row.entity_cd}" 
                                            data-doc_no="${row.doc_no}" 
                                            data-user_id="${row.user_id}" 
                                            data-level_no="${row.level_no}" 
                                            data-approve_seq="${row.approve_seq}">
                                            Re-Send Email
                                        </button>`;
                            }
                        }
                    ]
                });

                $('#DataTables_Table_0').on('click', '.send-data', function() {
                    let entity_cd = $(this).data('entity_cd');
                    let doc_no = $(this).data('doc_no');
                    let user_id = $(this).data('user_id');
                    let level_no = $(this).data('level_no');
                    let approve_seq = $(this).data('approve_seq');

                    // Tampilkan overlay loading sebelum request dikirim
                    $('#loading-overlay').fadeIn();

                    $.ajax({
                        url: "{{ route('apprlist.sendData') }}", // Pastikan route ini sesuai
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}", // Diperlukan untuk Laravel
                            entity_cd: entity_cd,
                            doc_no: doc_no,
                            user_id: user_id,
                            level_no: level_no,
                            approve_seq: approve_seq
                        },
                        success: function(response) {
                            console.log(response); // Debugging di console browser
                            // alert("Hasil Query:\n" + JSON.stringify(response, null, 2)); // Tampilkan hasil query dalam alert
                            alert("EMAIL RESEND");
                        },
                        error: function(xhr, status, error) {
                            alert("Terjadi kesalahan: " + xhr.responseText);
                        },
                        complete: function() {
                            // Sembunyikan overlay loading setelah request selesai
                            $('#loading-overlay').fadeOut();
                            table.ajax.reload(null, false);
                        }
                    });
                });
            });
        </script>
    </body>
</html>