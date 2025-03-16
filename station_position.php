<?php
const DB_SERVER   = "192.168.2.3";
const DB_USERNAME = "stone_test";
const DB_PASSWORD = "Pks2pZD9LQCYLpCI75k5fw";
const DB_NAME     = "stone_test";

//建立連線
function create_connection()
{
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        echo json_encode(["state" => false, "message" => "連線失敗!"]);
        exit;
    }
    return $conn;
}

//取得JSON的資料
function get_json_input()
{
    $data = file_get_contents("php://input");
    return json_decode($data, true);
}


//回復JOSN訊息
//state: 狀態(成功或失敗) message: 訊息內容 data: 回傳資料(可有可無)
function respond($state, $message, $data = null)
{
    echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
}

//取得所有資料
//input: none
// {"state" : true, "message" : "取得所有位置資料成功"}
// {"state" : false, "message" : "取得所有位置資料失敗"}
function get_alldata()
{
    $conn = create_connection();

    $stmt = $conn->prepare("SELECT * FROM station_position ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得所有位置成功", $mydata);
    } else {
        //查無資料
        respond(false, "查無資料");
    }
    $stmt->close();
    $conn->close();
}

function count_station()
{
    $conn = create_connection();

    $sql = "SELECT count(*) as count_staitionName FROM station_position";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "店面人數統計成功", $row);
    } else {
        respond(false, "店面統計失敗與相關錯誤訊息");
    }

    $conn->close();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        

        default:
            respond(false, "無效的操作");;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getalldata':
            get_alldata();
            break;
        case 'count':
            count_station();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'delete':
            
            break;

        default:
            respond(false, "無效的操作");
    }
} else {
    respond(false, "無效的請求方法");
}
