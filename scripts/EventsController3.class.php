<?php

require_once "db.function.php";
require_once  "UserContoller3.class.php";
require_once (dirname(__FILE__) ."/Model/Role.php");

use UserController as userController;
class EventsController
{
    public static function resolveRequest()
    {
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        switch ($requestMethod) {
            case "GET":
                return self::resolveAction($_GET);
            default:
                return null;
        }
    }

    public static function getLastEventsUpdateTime()
    {
        $query = "SELECT TABLE_NAME,CREATE_TIME,UPDATE_TIME
        FROM information_schema.tables
        WHERE table_schema = 'epicore' AND TABLE_NAME LIKE 'event%'";

        $db = getDB();
        $response = $db->getAll($query);
        return $response;
    }
    

    private static function resolveAction($params)
    {
        if (!isset($params["action"])) {
            return null;
        }
        $action = $params["action"];
        switch ($action) {
            case "get_events":
                return  self::getEvents($params);
            case "get_public_events":
                return  self::getPublicEvents($params);
            case "get_event_summary":
                return self::getEventSummary($params);
            case "get_last_events_update_time":
                return self::getLastEventsUpdateTime();
            default:
                return null;
        }
    }

    private static function getRoleIdFromCookie()
    {
        $role = null;
        if(isset($_COOKIE['epiUserInfo']))
        {
            $cookie = $_COOKIE['epiUserInfo'];
            $cookie = json_decode($cookie , true);
            if(isset($cookie['role']['roleId']))
            {
                if($cookie['role']['roleId'] == Role::responder)
                {
                    $role = $cookie['role']['roleId'];
                }
            }
        }

        return $role;
    }


    private static function getEvents($params)
    {
        if (!userController::isUserValid()) {
            echo json_encode(false);
            return false;
        }

        $requester_id = null;
        $start_date = null;
        $end_date = null;
        $is_open = null;
        $organization_id = null;
        $fetp_id = null;
        $roleId = null;
        $optionalFields = [];
        $conditions = [];


        if (isset($params["uid"])) {
            $requester_id = userController::getUserData()["uid"];
        }

        if(isset(userController::getUserData()["fetp_id"])){
            $fetp_id = userController::getUserData()["fetp_id"];
        }

        if (isset($params["start_date"])) {
            $start_date = $params["start_date"];
         }

        if (isset($params["end_date"])) {
            $end_date = $params["end_date"];
        }

        if (isset($params["is_open"])) {
            $is_open = filter_var($params["is_open"], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($params["organization_id"])) {
            $organization_id = userController::getUserData()["organization_id"];
        }

        if ($requester_id) {
            array_push($conditions, "event.requester_id = '$requester_id'");
        }

        if ($organization_id) {
            array_push($conditions, "user.organization_id = '$organization_id'");
        }

        if ($start_date) {
            array_push($conditions, "event.create_date >= '$start_date'");
        }

        if ($end_date) {
            array_push($conditions, "event.create_date <= '$end_date'");
        }

        if($fetp_id){
            array_push($conditions, "event_fetp.fetp_id = '$fetp_id'");
        }

        if (!$is_open) {
            array_push($optionalFields, "purpose.phe_description");
        }

        if(!is_null(self::getRoleIdFromCookie())) //add only if responder
        {
            $roleId = self::getRoleIdFromCookie();
            array_push($conditions, "fetp.roleId = $roleId");
        }

        $query = "
            SELECT distinct 
                event.event_id,
                event.title,
                DATE_FORMAT(event.create_date, '%d-%M-%Y') AS create_date,
                DATE_FORMAT(event.create_date, '%Y-%m-%dT%h:%i:%s') AS iso_create_date,
                DATE_FORMAT(event_notes.action_date, '%d-%M-%Y') AS action_date,
                DATE_FORMAT(event_notes.action_date, '%Y-%m-%dT%h:%i:%s') AS iso_action_date,
                event.requester_id, 
                user.user_id, hmutbl.name AS person,
                organization.name AS organization_name,
                place.name AS country, 
                purpose.outcome AS outcome,
                event_notes.status,
                purpose.phe_description,
                purpose.phe_additional
            
            FROM place
                INNER JOIN event on event.place_id = place.place_id
                INNER JOIN user ON event.requester_id = user.user_id
                INNER JOIN organization ON user.organization_id = organization.organization_id
                INNER JOIN hm_hmu hmutbl ON hmutbl.hmu_id = user.hmu_id
                INNER JOIN event_fetp ON event.event_id = event_fetp.event_id
                INNER JOIN fetp ON fetp.fetp_id = event_fetp.fetp_id
                INNER JOIN purpose ON event.event_id = purpose.event_id
                LEFT OUTER JOIN (
                    SELECT 
                           event_notes_id, 
                           event_id, 
                           status, 
                           action_date
                    FROM event_notes
                    WHERE 
                          event_notes_id IN (
                              SELECT 
                                max(event_notes_id) 
                              FROM event_notes 
                              GROUP BY event_id
                          )
                ) event_notes ON event.event_id = event_notes.event_id
            ";
        if ($is_open) {
            array_push($conditions, "(event_notes.status = 'O' OR event_notes.status is NULL)");
        } else {
            array_push($conditions, "event_notes.status = 'C'");
        }

        $query = self::addQueryWhereConditions($query, $conditions);
        $query .= " order by event.create_date DESC";
        
        $db = getDB();
        $q = $db->query($query);

        // Post processing for all counts
        $results = array();
        while($row = $q->fetchRow()) {

            $row['num_members'] = $db->getOne("SELECT COUNT(DISTINCT(fetp_id)) FROM event_fetp WHERE event_id = ?", array($row['event_id']));

            $row['num_responses'] = $db->getOne("SELECT COUNT(*) FROM response WHERE event_id = ?", array($row['event_id']));

            $row['num_responses_content'] = $db->getOne("SELECT COUNT(*) FROM response WHERE event_id = ? AND response_permission > 0 AND  response_permission < 4", array($row['event_id']));

            $row['num_responses_active'] = $db->getOne("SELECT COUNT(*) FROM response WHERE event_id = ?  AND response_permission = 4", array($row['event_id']));

            $row['num_notrated_responses'] = $db->getOne("SELECT COUNT(*) FROM response WHERE useful IS NULL AND response_permission <>0 AND response_permission <>4 and event_id = ?", array($row['event_id']));

            $row['no_active_14_days'] = $db->getOne("SELECT COUNT(*) FROM event_fetp WHERE event_id = ? AND event_fetp.send_date <= (CURDATE() - INTERVAL 14 DAY)", array($row['event_id']));

            $results[] = $row;
            
        }
        return $results;

    }

    private static function getPublicEvents($params)
    {
        $start_date = null;
        $end_date = null;
        $conditions = [];
        
        if (isset($params["start_date"])) {
            $start_date = $params["start_date"];
        }

        if (isset($params["end_date"])) {
            $end_date = $params["end_date"];
        }

        if ($start_date) {
            array_push($conditions, "event.create_date >= '$start_date'");
        }

        if ($end_date) {
            array_push($conditions, "event.create_date <= '$end_date'");
        }

        array_push($conditions,"event_notes.status = 'C'");

        array_push($conditions,"purpose.outcome IN ('VP', 'VN' , 'UP')");

        $query = "SELECT distinct
        event.event_id,
        event.title,
        DATE_FORMAT(event.create_date, '%d-%M-%Y') AS create_date,
        DATE_FORMAT(event.create_date, '%Y-%m-%dT%h:%i:%s') AS iso_create_date,
        DATE_FORMAT(event_notes.action_date, '%d-%M-%Y') AS action_date,
        DATE_FORMAT(event_notes.action_date, '%Y-%m-%dT%h:%i:%s') AS iso_action_date,
        purpose.outcome AS outcome,
        place.name AS country 
        
        FROM place

        INNER JOIN event 
        ON event.place_id = place.place_id
        
        INNER JOIN event_fetp 
        ON event.event_id = event_fetp.event_id
        
        LEFT OUTER JOIN (
            SELECT event_notes_id, event_id, status, action_date
            FROM event_notes
            where event_notes_id IN (SELECT max(event_notes_id) FROM event_notes GROUP BY event_id)
        ) event_notes 
        ON event.event_id = event_notes.event_id

        INNER JOIN purpose 
        ON event.event_id = purpose.event_id

        ";

        $query = self::addQueryWhereConditions($query, $conditions);

        
        $db = getDB();
        $response = $db->getAll($query);
        return $response;
    }

    private static function getEventSummary($params)
    {
        if (!userController::isUserValid()) {
            echo json_encode(false);
            return false;
        }
        
        if (!isset($params['event_id'])) {
            return null;
        }

        $event_id = $params['event_id'];

        $query = "SELECT
        source.source, source.details, purpose.phe_additional
        FROM source
        INNER JOIN purpose
        ON source.event_id = purpose.event_id
        WHERE source.event_id = '$event_id'";
        
        $db = getDB();
        $response = $db->getRow($query);
        return $response;
    }

    private static function addQueryOptionalFields($query, $optionalFields)
    {
        foreach ($optionalFields as $key => $value) {
            if ($key === 0) {
                $query .= ", ";
            }
            $query .= $value;
            if ($key < sizeof($optionalFields) - 1) {
                $query .= ", ";
            }
        }
        return $query;
    }

    private static function addQueryWhereConditions($query, $conditions)
    {
        foreach ($conditions as $key => $value) {
            if ($key === 0) {
                $query .= "WHERE ";
            }
            $query .= $value;
            if ($key < sizeof($conditions) - 1) {
                $query .= " AND ";
            }
        }
        return $query;
    }
}
