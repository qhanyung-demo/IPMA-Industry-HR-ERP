<?php

namespace App\Jobs;

// load batch and queue
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;

// load db facade
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

// load models
use App\Models\Staff;
use App\Models\HumanResources\OptWeekDates;


// load helper
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

// load lib
use \Carbon\Carbon;
use \Carbon\CarbonPeriod;
use \Carbon\CarbonInterval;

use Session;
use Throwable;
use Log;
use Exception;

class ConditionalIncentiveJob implements ShouldQueue
{
	use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $staffs;
	protected $request;

	/**
	 * Create a new job instance.
	 */
	public function __construct($staffs, $request)
	{
		$this->staffs = $staffs;
		$this->request = $request;
		// dd($staffs, $request);
	}

	/**
	 * Execute the job.
	 */
	public function handle()//: void
	{
		$staffs = $this->staffs;
		$request = $this->request;

		for ($i=$request['date_from']; $i <= $request['date_to']; $i++) {
			// $week_id[] = OptWeekDates::select('id', 'date_from', 'date_to', 'week')->where('id', $i)->get();
			$week_id[] = $i;
		}

		$handle = fopen(storage_path('app/public/excel/cistaff.csv'), 'a+') or die();

		$incentivestaffs = Staff::select('staffs.id', 'logins.username', 'staffs.name')->join('logins', 'staffs.id', '=', 'logins.staff_id')->orderBy('logins.username')->whereIn('staffs.id', $staffs)->where('logins.active', 1)->get();

		foreach ($incentivestaffs as $k1 => $v1) {
			$users[$k1] = $v1->username.' - '.$v1->name;
			foreach ($v1->belongstomanycicategoryitem()?->get() as $k2 => $v2) {
				$desc[$k1][$k2] = $v2->description;
				// $userIncentive[$k1][$k2] = Arr::flatten(Arr::crossJoin([$users[$k1], $desc[$k1][$k2]]));
				$userIncentive[$k1][$k2] = Arr::crossJoin($users[$k1], $desc[$k1][$k2]);
				foreach (OptWeekDates::whereIn('id', $week_id)->get() as $k3 => $v3) {
					$weeks[$k1][$k2][$k3] = $v3->week.' ('.Carbon::parse($v3->date_from)->format('j M Y').' - '.Carbon::parse($v3->date_to)->format('j M Y').')';
					// $userIncentiveWeeks[$k1][$k2][$k3] = Arr::flatten(Arr::crossJoin($userIncentive[$k1][$k2], $weeks[$k1][$k2][$k3]));
					$userIncentiveWeeks[$k1][$k2][$k3] = [$userIncentive[$k1][$k2], $weeks[$k1][$k2][$k3]];
				}
			}
			$records[$k1] = $userIncentiveWeeks[$k1];
		}

		dump($records);

		// foreach ($records as $k1 => $v1) {
		// 	// foreach ($v1 as $k2 => $v2) {
		// 		fputcsv($handle, $v1);
		// 	// }
		// }
		// fclose($handle);
	}
}
