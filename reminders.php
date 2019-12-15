<?php
class Reminder {
    function __construct($id,$title,$dt,$creation_timestamp_msec = null,$done = false) {
        if ($id == null) {
            throw new Exception('Reminder id must not be None');
        }
        $this->id = $id;
        $this->title = $title;
        $this->dt = $dt;
        $this->creation_timestamp_msec = $creation_timestamp_msec;
        $this->done = $done;
    }
}

function create_reminder_request_body($reminder) {
    $body = (object)[
        '2' => (object)[
            '1' => 7
        ],
        '3' => (object)[
            '2' => $reminder->id
        ],
        '4' => (object)[
            '1' => (object)[
                '2' => $reminder->id
            ],
            '3' => $reminder->title,
            '5' => (object)[
                '1' => $reminder->dt->year,
                '2' => $reminder->dt->month,
                '3' => $reminder->dt->day,
                '4' => (object)[
                    '1' => $reminder->dt->hour,
                    '2' => $reminder->dt->minute,
                    '3' => $reminder->dt->second,
                ]
            ],
            '8' => 0
        ]
    ];

    return json_encode($body);
}

function get_reminder_request_body($reminder_id) {
    $body = (object)['2' => [(object)['2' => $reminder_id]]];

    return json_encode($body);
}

function delete_reminder_request_body($reminder_id) {
    $body = (object)['2' => [(object)['2' => $reminder_id]]];

    return json_encode($body);
}

function list_reminder_request_body($num_reminders, $max_timestamp_msec = 0) {
    /*
    The body corresponds to a request that retrieves a maximum of num_reminders reminders, 
    whose creation timestamp is less than max_timestamp_msec.
    max_timestamp_msec is a unix timestamp in milliseconds. 
    if its value is 0, treat it as current time.
    */
    $body = (object)[
        '5' => 1,  // boolean field: 0 or 1. 0 doesn't work ¯\_(ツ)_/¯
        '6' => $num_reminders,  // number of reminders to retrieve
    ];
    
    if ($max_timestamp_msec) {
        $max_timestamp_msec += int(15 * 3600 * 1000);
        $body['16'] = $max_timestamp_msec;
        /*
        Empirically, when requesting with a certain timestamp, reminders with the given timestamp 
        or even a bit smaller timestamp are not returned. 
        Therefore we increase the timestamp by 15 hours, which seems to solve this...  ~~voodoo~~
        (I wish Google had a normal API for reminders)
        */
    }

    return json_encode($body);
}

function build_reminder($reminder_dict) {
    $r = $reminder_dict;

    try {
        $id = $r['1']['2'];
        $title = $r['3'];
        $year = $r['5']['1'];
        $month = $r['5']['2'];
        $day = $r['5']['3'];
        $hour = $r['5']['4']['1'];
        $minute = $r['5']['4']['2'];
        $second = $r['5']['4']['3'];
        $creation_timestamp_msec = int($r['18']);
        $done = array_key_exists('8', $r) && $r['8'] == 1;
        
        return new Reminder(
            $id,
            $title,
            DateTime($year, $month, $day, $hour, $minute, $second),
            $creation_timestamp_msec,
            $done
        );
    }
    catch (Exception $KeyError) {
        echo('build_reminder failed: unrecognized reminder dictionary format');
        
        return null;
    }
}
    
function create_reminder($httpClient, $reminder) {
    /*
    send a 'create reminder' request.
    returns True upon a successful creation of a reminder
    */
    $response = $httpClient->request(
        'POST',
        'https://reminders-pa.clients6.google.com/v1internalOP/reminders/create',
        [
            'headers' => [ 'content-type' => 'application/json' ],
            'body' => create_reminder_request_body($reminder),
        ]
    );

    if ($response->getStatusCode() == 200) {
        $content = $response->getBody();
        return true;
    }
    else {
        return false;
    }
}

function get_reminder($httpClient, $reminder_id) {
    /*
    retrieve information about the reminder with the given id. 
    None if an error occurred
    */
    $response = $httpClient->request(
        'POST',
        'https://reminders-pa.clients6.google.com/v1internalOP/reminders/get',
        [
            'headers' => [ 'content-type' => 'application/json' ],
            'body' => get_reminder_request_body($reminder_id)
        ]
    );

    if ($response->getStatusCode() == 200) {

        $content = $response->getBody();
        $content_dict = json_decode($content);

        if (!isset($content_dict) || empty($content_dict)) {
            echo("Couldn\'t find reminder with id=${reminder_id}");
            return null;
        }

        $reminder_dict = $content_dict['1'][0];

        return build_reminder($reminder_dict);
    }
    else {
        return null;
    }
}

function delete_reminder($httpClient, $reminder_id) {
    /*
    delete the reminder with the given id.
    Returns True upon a successful deletion
    */
    $response = $httpClient->request(
        'POST',
        'https://reminders-pa.clients6.google.com/v1internalOP/reminders/delete',
        [
            'headers' => [ 'content-type' => 'application/json' ],
            'body' => delete_reminder_request_body($reminder_id)
        ]
    );

    if ($response->getStatusCode() == 200) {
        $content = $response->getBody();
        return true;
    }
    else {
        return false;
    }
}

function list_reminders($httpClient, $num_reminders) {
    /*
    returns a list of the last num_reminders created reminders, or
    None if an error occurred
    */
    $response = $httpClient->request(
        'POST',
        'https://reminders-pa.clients6.google.com/v1internalOP/reminders/list',
        [
            'headers' => [ 'content-type' => 'application/json' ],
            'body' => list_reminder_request_body($num_reminders)
        ]
    );

    if ($response->getStatusCode() == 200) {

        $content = $response->getBody();
        $content_dict = json_decode($content);

        if (!array_key_exists('1', $content_dict)) {
            return [];
        }

        $reminders_dict_list = $content_dict['1'];
        $reminders = [];

        foreach($reminders_dict_list as $reminder_dict) {
            array_push($reminders, build_reminder($reminder_dict));
        }

        return $reminders;
    }
    else {
        return null;
    }
}