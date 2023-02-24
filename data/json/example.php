<?php

namespace App\Http\Controllers\Backend\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Pangkat;
use App\Models\Instansi;
use Config;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
   * Show the application dashboard.
   * More info DataTables : https://yajrabox.com/docs/laravel-datatables/master
   *
   * @param Datatables $datatables
   * @param Request $request
   * @return Application|Factory|Response|View
   * @throws \Exception
   */
  public function index(Datatables $datatables, Request $request)
  {
    
    $columns = !Auth::user()->hasRole('adminInstansi') 
      ? ['instansi']
      : [];

    $columns = array_merge($columns, [
      'NIP' => ['NIP' => 'user.NIP'],
      'name' => ['name' => 'user.name', 'title' => "Nama"],
      'jabatan',
      'bagian',
      'pangkat/golongan',
      'date' => ['title' => 'Tanggal'],
      'in_time' => ['title' => 'Jam Masuk'],
      'out_time' => ['title' => 'Jam Keluar'],
      'in_location_id' => ['name' => 'areaIn.name', 'title' => 'Area Masuk'],
      'out_location_id' => ['name' => 'areaOut.name', 'title' => 'Area Keluar'],
      'Keterangan'
    ]);

    $from = date($request->dateFrom);
    $to = date($request->dateTo);
    $permonth = date($request->perMonth);
    $_instansi = $request->instansi;
    $_abjad = $request->abjad;

    if ($datatables->getRequest()->ajax()) {
      $query = Attendance::with('user', 'areaIn', 'areaOut')
        ->select('attendances.*');

      if ($from && $to) {
        $query = $query->whereBetween('date', [$from, $to]);
      }
      else if ($permonth) {
        $query = $query->where('date', 'LIKE', $permonth . "-%");
      }
      
      if($_instansi != "" && Auth::user()->hasRole('administrator')){
        $query = $query->whereHas('user', function($q) use ($_instansi){
          $q->where('instansi', $_instansi);
        });
      }

      // Admin Instansi
      if (Auth::user()->hasRole('adminInstansi')) {
        $query = $query->whereHas('user', function($q) use ($_abjad){
          $q->where('instansi', Auth::user()->instansi);
          if($_abjad){
            $q->where('name', 'LIKE', $_abjad . '%');
          }
        });
      }            

      // worker
      else if (Auth::user()->hasRole('staff')) {
        $query = $query->where('worker_id', Auth::user()->id);
      }

      $datatable = $datatables->eloquent($query);

      if(!Auth::user()->hasRole('adminInstansi'))
        $datatable = $datatable ->addColumn('instansi', function (Attendance $data) {
          return is_numeric($data->user->instansi) 
          //  ? (Instansi::find($data->user->instansi)->nama_instansi ?? "")
          //  : $data->user->instansi;
          ? (Instansi::find($data->user->instansi)->nama_instansi ?? "") : "";
        });
      $datatable = $datatable->addColumn('NIP', function (Attendance $data) {
          return "<span style='white-space: nowrap;width: 1px'>{$data->user->NIP}</span>";
        })
        ->addColumn('name', function (Attendance $data) {
          return "<span style='white-space: nowrap;width: 1px'>{$data->user->name}</span>";
        })
        ->addColumn('jabatan', function (Attendance $data) {
          return $data->user->jabatan;
        })
        ->addColumn('bagian', function (Attendance $data) {
          return $data->user->bagian;
        })
        ->addColumn('pangkat/golongan', function (Attendance $data) {
          // if(!empty($data->user->pangkat) && !empty($data->user->golongan))
          //     return $data->user->pangkat.' / '.$data->user->golongan;
          // else if(!empty($data->user->pangkat) || !empty($data->user->golongan))
          //     return $data->user->pangkat ?? $data->user->golongan;
          // else
          //     return "";
          return str_replace(" - ", " / ", Pangkat::find($data->user->pangkat)->nama_pangkat);
        })
        ->addColumn('in_location_id', function (Attendance $data) {
          // Ups
          // $user = User::join("areas", "areas.id", "users.area")->select(["areas.name"])->find($data->user->id);
          return $data->in_location_id == null ? '' : $data->areaIn->name;
          // return $user->name ?? "";
        })
        ->addColumn('out_location_id', function (Attendance $data) {
          // Ups
          // $user = User::join("areas", "areas.id", "users.area")->select(["areas.name"])->find($data->user->id);
          return $data->out_location_id == null ? '' : $data->areaOut->name;
          // return $user->name ?? "";
        })
        ->addColumn('Keterangan', function (Attendance $data) {
          // return $data->out_location_id == null ? '' : $data->areaOut->name;
          return $data->in_time != null ? 'Hadir' : "Alpha";
        })
        ->rawColumns(['name', 'out_location_id', 'in_location_id'])
        ->escapeColumns([])
        ->toJson();

      
      // $j = 0;
      // for($i = $totalDay; $i >= 1;$i--){
      //     if(!isset($attendance[$j])){
      //         $date = $moonYear . ($i > 9 ? $i : "0{$i}");
      //         $d = [
      //             "id"=> null,
      //             "worker_id"=> $r->id,
      //             "date" => $date,
      //             "in_time"=> null,
      //             "out_time"=> null,
      //             "work_hour"=> null,
      //             "over_time"=> null,
      //             "late_time"=> null,
      //             "early_out_time"=> null,
      //             "in_location_id"=> null,
      //             "out_location_id"=> null,
      //             // "created_at"=> "2023-02-16 01:00:23",
      //             // "updated_at"=> "2023-02-16 01:00:23",

      //             "status"=> "Alpha",
      //         ];
      //         $d['dayName'] = date("l", strtotime($d['date']));
                  
      //         $data[] = $d;
              
      //         continue;
              
      //     }
      //     $getDate = explode("-", $attendance[$j]['date']);
          
      //     $d = $attendance[$j];
      //     if($i == ((int) end($getDate))){
      //         $d["in_location_id"] = $d["in_location_id"] != null ? Area::find($d["in_location_id"])->address : null;
      //         $d["out_location_id"] = $d["out_location_id"] != null ? Area::find($d["out_location_id"])->address : null;
      //         $d['status'] = "Hadir";
      //         $j++;
      //     }
      //     else{
      //         $date = $moonYear . ($i > 9 ? $i : "0{$i}");
      //         $d = [
      //             "id"=> null,
      //             "worker_id"=> $r->id,
      //             "date" => $date,
      //             "in_time"=> null,
      //             "out_time"=> null,
      //             "work_hour"=> null,
      //             "over_time"=> null,
      //             "late_time"=> null,
      //             "early_out_time"=> null,
      //             "in_location_id"=> null,
      //             "out_location_id"=> null,
      //             // "created_at"=> "2023-02-16 01:00:23",
      //             // "updated_at"=> "2023-02-16 01:00:23",

      //             "status"=> "Alpha",
      //         ];
      //     }
      //     $d['dayName'] = date("l", strtotime($d['date']));
              
      //     $data[] = $d;
      // }

      return $datatable;
         
        
    }

    $columnsArrExPr = [0,1,2,3,4,5,6,7,8,9,10];
    $html = $datatables->getHtmlBuilder()
      ->columns($columns)
      ->minifiedAjax('', $this->scriptMinifiedJs())
      ->parameters([
        'order' => [[1,'desc'], [2,'desc']],
        'responsive' => true,
        'autoWidth' => false,
        'scrollX' => true,
        'lengthMenu' => [
          [ 10, 25, 50, -1 ],
          [ '10 rows', '25 rows', '50 rows', 'Show all' ]
        ],
        'dom' => 'Bfrtip',
        'buttons' => $this->buttonDatatables($columnsArrExPr),
      ]);
      
      
    $instansi = Instansi::orderBy("nama_instansi");
    $instansi = Auth::user()->hasRole('administrator') ? $instansi->get() : $instansi->where('id', Auth::user()->instansi)->get();

    return view('backend.attendances.index', compact('html', 'instansi'));
  }

  /**
   * Fungtion show button for export or print.
   *
   * @param $columnsArrExPr
   * @return array[]
   */
  public function buttonDatatables($columnsArrExPr)
  {
    if(Auth::user()->hasRole('adminInstansi')){
      return [
        [
          'pageLength'
        ],
        [
          'text' => 'Unduh PDF',
          'extend' => 'pdfHtml5',
          'orientation' => 'landscape',
          'exportOptions' => [
            'columns' => $columnsArrExPr
          ],
          'customize' => "
            function (doc) {
              doc.pageMargins = [10,10,10,10];
              doc.defaultStyle.fontSize = 7;
              doc.styles.tableHeader.fontSize = 7;
              doc.styles.title.fontSize = 9;
              // Remove spaces around page title
              doc.content[0].text = doc.content[0].text.trim();
              // Create a header
              doc['header']=(function(page, pages) {
                return {
                  columns: [
                    // 'This is your left footer column',
                    {
                      // This is the right column
                      alignment: 'right',
                      text: ['page ', { text: page.toString() },  ' of ', { text: pages.toString() }]
                    }
                  ],
                  margin: [10, 0]
                }
              });
              // Styling the table: create style object
              var objLayout = {};
              // Horizontal line thickness
              objLayout['hLineWidth'] = function(i) { return .5; };
              // Vertikal line thickness
              objLayout['vLineWidth'] = function(i) { return .5; };
              // Horizontal line color
              objLayout['hLineColor'] = function(i) { return '#aaa'; };
              // Vertical line color
              objLayout['vLineColor'] = function(i) { return '#aaa'; };
              // Left padding of the cell
              objLayout['paddingLeft'] = function(i) { return 4; };
              // Right padding of the cell
              objLayout['paddingRight'] = function(i) { return 4; };
              // Inject the object in the document
              doc.content[1].layout = objLayout;
            }"
        ],
      ];
    }

    return [
      [
        'pageLength'
      ],
      [
        'extend' => 'csvHtml5',
        'exportOptions' => [
          'columns' => $columnsArrExPr
        ]
      ],
      [
        'extend' => 'pdfHtml5',
        'orientation' => 'landscape',
        'exportOptions' => [
          'columns' => $columnsArrExPr
        ],
        'customize' => "
          function (doc) {
            doc.pageMargins = [10,10,10,10];
            doc.defaultStyle.fontSize = 7;
            doc.styles.tableHeader.fontSize = 7;
            doc.styles.title.fontSize = 9;
            // Remove spaces around page title
            doc.content[0].text = doc.content[0].text.trim();
            // Create a header
            doc['header']=(function(page, pages) {
              return {
                columns: [
                  // 'This is your left footer column',
                  {
                    // This is the right column
                    alignment: 'right',
                    text: ['page ', { text: page.toString() },  ' of ', { text: pages.toString() }]
                  }
                ],
                margin: [10, 0]
              }
            });
            // Styling the table: create style object
            var objLayout = {};
            // Horizontal line thickness
            objLayout['hLineWidth'] = function(i) { return .5; };
            // Vertikal line thickness
            objLayout['vLineWidth'] = function(i) { return .5; };
            // Horizontal line color
            objLayout['hLineColor'] = function(i) { return '#aaa'; };
            // Vertical line color
            objLayout['vLineColor'] = function(i) { return '#aaa'; };
            // Left padding of the cell
            objLayout['paddingLeft'] = function(i) { return 4; };
            // Right padding of the cell
            objLayout['paddingRight'] = function(i) { return 4; };
            // Inject the object in the document
            doc.content[1].layout = objLayout;
          }"
      ],
      [
        'extend' => 'excelHtml5',
        'exportOptions' => [
          'columns' => $columnsArrExPr
        ]
      ],
      [
        'extend' => 'print',
        'orientation' => 'landscape',
        'exportOptions' => [
          'columns' => $columnsArrExPr
        ]
      ],
    ];
  }

  /**
   * Get script for the date range.
   *
   * @return string
   */
  public function scriptMinifiedJs()
  {
    // Script to minified the ajax
    return <<<CDATA
      var formData = $("#date_filter").find("input").serializeArray();
      $.each(formData, function(i, obj){
        data[obj.name] = obj.value;
      });
      
      data['perMonth'] = $("#permonth").val();
      data['instansi'] = $(".instansi").length > 0 ? $(".instansi").val() : null;
      data['abjad'] = $(".abjad").length > 0 ? $(".abjad").val() : null;
CDATA;
  }
}
