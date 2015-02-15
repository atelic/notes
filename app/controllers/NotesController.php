<?php

class NotesController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	public function note() {
		if(Input::get("note") !== null) {
            
            // HACK ALERT Pass back the note but with <br>'s changed to line breaks \n's
			// if someone is logged in, see if they own the note - if so show it
			if(Auth::check() && !is_null(Note::find(Input::get("note")))) {
    			$note_raw = Note::where("id",Input::get("note"))->where("user_id",Auth::user()->id)->get()->first();
    			if(!is_null($note_raw)){
     			   $note = preg_replace('#<br\s*/?>#i', "", $note_raw->note);
                    Log::info($note);
                    return View::make("note")->with("note",$note)->with("id",$note_raw->id);   			
    			} else {
        			return View::make("note")->with("note","This note is private")->with("id","0");
    			}
            // If the note doesn't exist 
			} else if(is_null(Note::find(Input::get("note")))) {
    			return View::make("note")->with("note","That note doesn't exist")->with("id","0");
            // If the note exists but does not belong to the user (later: public/private flag)
			} else if(!Auth::check()) {
    			return View::make("note")->with("note","This note is private and you don't have permission to view it")->with("id","0");
			}
		} else {
			return View::make("note");
		}
	}

	public function save() {
		// routing function for save or create
		if(Input::get("id")) {
			return $this->update();
		} else {
			return $this->create();
		}
	}

	public function create()
	{
        Log::info(Input::get("note_text"));
        Log::info(nl2br(Input::get("note_text")));
		$note = new Note;
		$note->note = Input::get("note_text");
		// this needs to chekc if user exists, if not great a temp one and store the session in the browser somehow
		if(Auth::check()) {
			$note->user_id = Auth::user()->id;
			$note->save();
			Log::info($note->id);
			return Response::json(array('success' => true, 'insert_id' => $note->id), 200);
		} else {
			return Response::json(array('success' => false, 'insert_id' => null), 201);
		}
	}

	public function update() {
        Log::info(Input::get("note_text"));
        Log::info(nl2br(Input::get("note_text")));
		$note = Note::find(Input::get("id"));
		$note->note = Input::get("note_text");
		$note->save();
		return Response::json(array('success' => true, 'insert_id' => null, 'saved' => true), 200);
	}

	public function data() {
		$notes = Note::where('user_id', Auth::user()->id)->where('archived',"!=",1)->get();
		$notes_return = array();
		foreach($notes as $note) {
			// format date created
	        $date = new DateTime($note->created_at, new DateTimeZone('UTC'));
			$date->setTimezone(new DateTimeZone('EST'));
			$formatted_date = $date->format('M j, Y g:i:s a');
			// format date updated
	        $date = new DateTime($note->updated_at, new DateTimeZone('UTC'));
			$date->setTimezone(new DateTimeZone('EST'));
			$formatted_date_updated = $date->format('M j, Y g:i:s a');
			$Parsedown = new Parsedown();
			$parsed_note = $Parsedown->text($note->note);
            array_push($notes_return, array(
                "date_created"=>array("date"=>$formatted_date,"id"=>$note->id),
                "date_updated"=>$formatted_date_updated,
				"note"=>$parsed_note,
				"note_raw"=>$note->note
            ));
		}
		// json encode response so it's usable
		$json = json_encode(array("data" => $notes_return));
		return $json;
	}

	public function archives() {
		$notes = Note::where('user_id', Auth::user()->id)->where('archived',1)->get();
		$notes_return = array();
		foreach($notes as $note) {
			// format date created
	        $date = new DateTime($note->created_at, new DateTimeZone('UTC'));
			$date->setTimezone(new DateTimeZone('EST'));
			$formatted_date = $date->format('M j, Y g:i:s a');
			// format date updated
	        $date = new DateTime($note->updated_at, new DateTimeZone('UTC'));
			$date->setTimezone(new DateTimeZone('EST'));
			$formatted_date_updated = $date->format('M j, Y g:i:s a');
			$Parsedown = new Parsedown();
			$parsed_note = $Parsedown->text($note->note);
            array_push($notes_return, array(
                "date_created"=>array("date"=>$formatted_date,"id"=>$note->id),
                "date_updated"=>$formatted_date_updated,
				"note"=>$parsed_note,
				"note_raw"=>$note->note
            ));
		}
		// json encode response so it's usable
		$json = json_encode(array("data" => $notes_return));
		return $json;
	}

	public function edit() {
		Log::info(Input::get("id"));
		$id = Input::get("id");
		$note = Note::find($id);
		$note->note = Input::get("note_text");
		Log::info(nl2br(Input::get("note_text")));
		$note->save();
		return "success";
	}

	public function delete() {
		Log::info(Input::get("id"));
		$id = Input::get("id");
		$note = Note::find($id);
		$note->delete();
		return "success";
	}
	public function archive() {
		Log::info(Input::get("id"));
		$id = Input::get("id");
		$note = Note::find($id);
		$note->archived = 1;
		$note->save();
		return "success";
	}
	// restore archived note
	public function restore() {
		Log::info(Input::get("id"));
		$id = Input::get("id");
		$note = Note::find($id);
		$note->archived = 0;
		$note->save();
		return "success";
	}
}
