<?php
    function create_connection(){
        $link = mysqli_connect("localhost", "owner01", "123456") or 
        die("連線錯誤" . mysqli_connect_error());

        return $link;
    }

    function execute_sql($link, $db, $sql){
        mysqli_select_db($link, $db) or 
        die("連線資料庫錯誤" . mysqli_errno($link));

        $result = mysqli_query($link, $sql);
        return $result;
    }
?>