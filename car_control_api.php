<?php
const DB_SERVER   = "192.168.2.3";
const DB_USERNAME = "stone_test";
const DB_PASSWORD = "Pks2pZD9LQCYLpCI75k5fw";
const DB_NAME     = "stone_test";

header('Content-Type: application/json');

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

// 車輛建檔
function create_car()
{
    $input = get_json_input();
    if (isset($input["car_Price"], $input["carName"], $input["car_Number"], $input["count"], $input["position"], $input["energy"], $input["remark"], $input["Photo"])) {
        $p_carPrice = $input["car_Price"];
        $p_carName = $input["carName"];
        $p_car_Number = $input["car_Number"];
        $p_count = $input["count"];
        $p_position = $input["position"];
        $p_energy = $input["energy"];
        $p_type = $input["type"];
        $p_remark = $input["remark"];
        $p_Photo = $input["Photo"];
        if ($p_carPrice && $p_carName && $p_car_Number && $p_count && $p_position && $p_energy && $p_type && $p_Photo) {
            $conn = create_connection();

            $stmt = $conn->prepare("INSERT INTO car(car_Price,carName,car_Number,count,position,energy,type,remark,Photo) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $p_carPrice, $p_carName, $p_car_Number, $p_count, $p_position, $p_energy, $p_type, $p_remark, $p_Photo);

            if ($stmt->execute()) {
                $last_id = mysqli_insert_id($conn);
                echo json_encode(['state' => true, 'status' => 'success', 'message' => '新增成功', 'id' => $last_id]);
            } else {
                respond(false, "註冊失敗");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

// 車牌重複判定
function num_check()
{
    $input = get_json_input();
    if (isset($input["car_Number"])) {
        $p_number = trim($input["car_Number"]);
        if ($p_number) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT car_Number FROM car WHERE car_Number = ?");
            $stmt->bind_param("s", $p_number); //一定要傳遞變數
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                //已存在
                respond(false, "車牌已存在");
            } else {
                //不存在
                respond(true, "正確格式");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

// 車輛清單
function list_car()
{
    $conn = create_connection();

    $stmt = $conn->prepare("SELECT id, car_Price, carName, car_Number, count, position, energy, type, remark, Photo, on_state ,Create_at FROM car ORDER BY position ASC");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得所有車輛資料成功", $mydata);
    } else {
        //查無資料
        respond(false, "查無資料");
    }
    $stmt->close();
    $conn->close();
}

function count_car()
{
    $conn = create_connection();

    $sql = "SELECT count(*) as count_position FROM car";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "車輛數量統計成功", $row);
    } else {
        respond(false, "車輛數量統計失敗與相關錯誤訊息");
    }

    $conn->close();
}

// 車輛更新
function update_car()
{
    $input = get_json_input();
    if (isset($input["id"], $input["car_Price"], $input["position"], $input["remark"], $input["on_state"])) {
        $p_id = $input["id"];
        $p_car_Price  = $input["car_Price"];
        $p_position  = $input["position"];
        $p_remark = $input["remark"];
        $p_state = $input["on_state"];
        if ($p_id && $p_car_Price && $p_position && $p_state) {
            $conn = create_connection();

            $stmt = $conn->prepare("UPDATE car SET car_Price = ?, position = ?, remark = ?, on_state = ? WHERE ID = ?");
            $stmt->bind_param("ssssi", $p_car_Price, $p_position, $p_remark, $p_state, $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "車輛更新成功");
                } else {
                    respond(false, "車輛更新失敗，並無更新行為!");
                }
            } else {
                respond(false, "車輛更新失敗");
            }

            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}

// 車輛刪除
function delete_car()
{
    $input = get_json_input();
    if (isset($input["id"])) {
        $p_id = trim($input["id"]);
        if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM car WHERE ID = ?");
            $stmt->bind_param("i", $p_id); //一定要傳遞變數

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "車輛刪除成功");
                } else {
                    respond(false, "車輛刪除失敗，並無刪除行為!");
                }
            } else {
                respond(false, "車輛刪除失敗");
            }

            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空");
        }
    } else {
        respond(false, "欄位錯誤");
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'create':
            create_car();
            break;

        case 'update':
            update_car();
            break;

        case 'numcheck':
            num_check();
            break;

        default:
            respond(false, "無效的操作");;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'carlist':
            list_car();
            break;
        case 'carcount':
            count_car();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'delete':
            delete_car();
            break;
        default:
            respond(false, "無效的操作");
    }
} else {
    respond(false, "無效的請求方法");
}
