<?php

use Carbon\Carbon;

class StatsController extends BaseController {

	// create a new user
	public function stats_page() {
    	$now = Carbon::now();
    	$today = Carbon::toDay();
    	$yesterday = Carbon::toDay()->subDay();
    	$thismonth = gmdate(date('n'));
    	$lastmonth = gmdate(date('n',strtotime("-1 month")));
    	$today_stats = array(
    	    "new users today" => User::whereBetween("created_at", array( $today,$now))->count(),
    	    "new notes today" => Note::whereBetween("created_at", array( $today,$now))->count(),
    	    "updated notes today" => Note::whereBetween("updated_at", array( $today,$now))->count(),
    	    "new gdocs today" => Gdoc::whereBetween("created_at", array( $today,$now))->count(),
        );
        $yesterday_stats = array(
    	    "new users yesterday" => User::whereBetween("created_at", array( $yesterday,$today))->count(),
    	    "new notes yesterday" => Note::whereBetween("created_at", array( $yesterday,$today))->count(),
    	    "updated notes yesterday" => Note::whereBetween("updated_at", array( $yesterday,$today))->count(),
    	    "new gdocs yesterday" => Gdoc::whereBetween("created_at", array( $yesterday,$today))->count(),
        );
        $thismonth_stats = array(
    	    "new users this month (".date('F').")" => User::where( DB::raw('MONTH(created_at)'), '=', $thismonth )->count(),
    	    "new notes this month (".date('F').")" => Note::where( DB::raw('MONTH(created_at)'), '=', $thismonth )->count(),

        );
        $lastmonth_stats = array(
    	    "new users last month (".date('F',strtotime("-1 month")).")" => User::where( DB::raw('MONTH(created_at)'), '=', $lastmonth )->count(),
    	    "new notes last month (".date('F',strtotime("-1 month")).")" => Note::where( DB::raw('MONTH(created_at)'), '=', $lastmonth )->count(),
        );
        $total = array(
    	    "total users" => DB::table('users')->count(),
    	    "total notes" => DB::table('notes')->count(),
        );
        $stats = array("today" => $today_stats,"yesterday" => $yesterday_stats,"thismonth" => $thismonth_stats,"lastmonth" => $lastmonth_stats,"total" => $total);
        Log::info($stats["today"]);
        return View::make('stats')->with("stats",$stats);
    }

		public function new_stats() {
			// return array
			$return = array();

			// array of tables in the database
			$all_tables = DB::select('SHOW TABLES');
			$tables = array();
			foreach ($all_tables as $key => $value) {
				array_push($tables,reset($value));
			}

			// get list of Model names
			$path = app_path() . "/Models";
			$models = $this->getModels($path);

			foreach ($models as $model) {
				$created_at_count = $this->createdAtCount("created_at",$model);
				$return[$model] = $created_at_count;
			}

			// structure what to return, in this instance all of the first model
			$users = $this->createdAtCount("created_at",$models[1]);

			return $return;
		}

		// get count by day based on time attribute such as "created_at" or "updated_at"
		private function createdAtCount($attr,$model) {
			$days_fetch = $model::select($attr)
			    ->get()
			    ->groupBy(function($date) {
			        return Carbon::parse($date->created_at)->format('d'); // grouping by years
			        //return Carbon::parse($date->created_at)->format('m'); // grouping by months
			    });
			 $days = array();
			 foreach ($days_fetch as $key => $value) {
				 $days[$key] = count($days_fetch[$key]);
			 }

			 return $days;
		}

		// get list of Model names
		private function getModels($path) {
			$out=array();
			$results = scandir($path);
			foreach($results as $result) {
				if($result === '.' or $result === '..') continue;
				$filename = $result;
				if(is_dir($filename)) {
					$out = array_merge($out, getModels($fileName));
				} else {
					$out[] = substr($filename,0,-4);
				}
			}
			return $out;
		}
}
