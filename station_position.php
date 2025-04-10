<?php
const DB_SERVER   = "";
const DB_USERNAME = "";
const DB_PASSWORD = "";
const DB_NAME     = "";

function create_connection()
{
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$conn) {
        echo json_encode(["state" => false, "message" => "連線失敗!"]);
        exit;
    }
    return $conn;
}

function get_json_input()
{
    $data = file_get_contents("php://input");
    return json_decode($data, true);
}

function respond($state, $message, $data = null)
{
    echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
}

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
        respond(false, "查無資料");
    }
    $stmt->close();
    $conn->close();
}

function get_citydata()
{
    $conn = create_connection();
    $stmt = $conn->prepare("SELECT cityName FROM station_position GROUP BY cityName ORDER BY MIN(id) ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row['cityName'];
        }
        respond(true, "取得所有不重複的城市名稱成功", $mydata);
    } else {
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
     $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getalldata':
            get_alldata();
            break;
        case 'count':
            count_station();
            break;
        case 'getcity':
            get_citydata();
            break;
        default:
            respond(false, "無效的操作");
    }
} 
else {
    respond(false, "無效的請求方法");
}
