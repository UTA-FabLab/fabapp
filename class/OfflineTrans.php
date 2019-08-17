<?php
class OfflineTrans {
    public static function byOffTransId($off_trans_id){
        global $mysqli;
        if ($result = $mysqli->query("
            SELECT *
            FROM `offline_transactions`
            WHERE `off_trans_id` = '$off_trans_id'
            LIMIT 1;
        "))
            $row = $result->fetch_assoc();
            $trans_id = $row['trans_id'];
            return $trans_id;
    }

    public static function byTransId($trans_id){
        global $mysqli;
        if ($result = $mysqli->query("
            SELECT *
            FROM `offline_transactions`
            WHERE `trans_id` = '$trans_id'
            LIMIT 1;
        "))
            $row = $result->fetch_assoc();
            $off_trans_id = $row['off_trans_id'];
            return $off_trans_id;
    }
}