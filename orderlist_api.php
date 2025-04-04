<?php
const DB_SERVER   = "192.168.2.3";
const DB_USERNAME = "stone_test";
const DB_PASSWORD = "Pks2pZD9LQCYLpCI75k5fw";
const DB_NAME     = "stone_test";

header('Content-Type:application/json');

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

function create_order()
{
    $input = get_json_input();
    if (isset($input["order_number"], $input["Username"], $input["Phone"], $input["carName"], $input["carNumber"], $input["totalPrice"], $input["station"], $input["borrowTime"], $input["returnTime"], $input["payment_method"])) {
        $p_order_number = $input["order_number"];
        $p_username = $input["Username"];
        $p_phone = $input["Phone"];
        $p_carName = $input["carName"];
        $p_carNumber = $input["carNumber"];
        $p_totalPrice = $input["totalPrice"];
        $p_station = $input["station"];
        $p_borrowTime = $input["borrowTime"];
        $p_returnTime = $input["returnTime"];
        $p_payment_method = $input["payment_method"];
        if ($p_order_number && $p_username && $p_phone && $p_carName && $p_carNumber && $p_totalPrice && $p_station && $p_borrowTime && $p_returnTime && $p_payment_method) {
            $conn = create_connection();
            $stmt2 = $conn->prepare("UPDATE car SET on_state = '借出' WHERE car_Number = ?");
            $stmt2->bind_param("s", $p_carNumber);
            $stmt1 = $conn->prepare("INSERT INTO orderlist(order_number, Username, Phone, carName, carNumber, totalPrice, station, borrowTime, returnTime, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt1->bind_param("sssssissss", $p_order_number, $p_username, $p_phone, $p_carName, $p_carNumber, $p_totalPrice, $p_station, $p_borrowTime, $p_returnTime, $p_payment_method);

            if ($stmt1->execute()) {
                if ($stmt2->execute()) {
                    $last_id = mysqli_insert_id($conn);
                    echo json_encode(['state' => true, 'status' => 'success', 'message' => '訂單成立', 'id' => $last_id]);
                }
            } else {
                respond(false, '訂單不成立');
            }
            $stmt1->close();
            $conn->close();
        } else {
            respond(false, '欄位不得為空');
        }
    } else {
        respond(false, '欄位錯誤');
    }
}

function get_alldata()
{
    $conn = create_connection();
    $stmt = $conn->prepare("SELECT * FROM orderlist ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得所有訂單資料", $mydata);
    } else {
        respond(false, "查無資料");
    }
    $stmt->close();
    $conn->close();
}

function count_yetlist()
{
    $conn = create_connection();

    // 正確的 SQL 語法，過濾 "未完成" 的訂單
    $sql = "SELECT COUNT(*) AS count_yetlistState FROM orderlist WHERE listState = '未完成'";

    $result = $conn->query($sql);

    if ($result) {
        $row = $result->fetch_assoc();
        respond(true, "未完成之訂單數量統計成功", $row);
    } else {
        respond(false, "未完成之訂單數量統計失敗: " . $conn->error);
    }

    $conn->close();
}

function count_donelist()
{
    $conn = create_connection();

    // 正確的 SQL 語法，過濾 "已完成" 的訂單
    $sql = "SELECT COUNT(*) AS count_donelistState FROM orderlist WHERE listState = '已完成'";

    $result = $conn->query($sql);

    if ($result) {
        $row = $result->fetch_assoc();
        respond(true, "已完成之訂單數量統計成功", $row);
    } else {
        respond(false, "已完成之訂單數量統計失敗: " . $conn->error);
    }

    $conn->close();
}

function user_order_check()
{
    $username = $_GET["Username"];
    $conn = create_connection();

    // 使用預處理語句來避免 SQL 注入
    $sql = "SELECT COUNT(*) AS order_count FROM orderlist WHERE Username = ? AND listState = '未完成'";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        respond(false, "SQL 準備失敗: " . $conn->error);
        return;
    }

    // 綁定參數並執行查詢
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['order_count'] > 0) {
            respond(false, "該用戶已有一筆未完成的訂單");
        } else {
            respond(true, $result);
        }
    } else {
        respond(false, "查詢失敗: " . $conn->error);
    }

    // 釋放資源
    $stmt->close();
    $conn->close();
}

function user_own_order()
{
    $username = $_GET["Username"];
    $conn = create_connection();

    // 使用預處理語句來避免 SQL 注入
    $sql = "SELECT * FROM orderlist WHERE Username = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        respond(false, "SQL 準備失敗: " . $conn->error);
        return;
    }

    // 綁定參數
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得使用者所有訂單資料", $mydata);
    } else {
        respond(false, "查無資料");
    }

    // 釋放資源
    $stmt->close();
    $conn->close();
}

// 更新付款狀態
function edit_payment_status()
{
    $input = get_json_input();
    if (isset($input["id"], $input["payment_status"])) {
        $p_id = trim($input["id"]);
        $p_payment_status = trim($input["payment_status"]);

        if ($p_id && $p_payment_status) {
            $conn = create_connection();
            $stmt = $conn->prepare("UPDATE orderlist SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $p_payment_status, $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "付款狀態更新成功");
                } else {
                    respond(false, "付款狀態未變更");
                }
            } else {
                respond(false, "付款狀態更新失敗");
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

// 更新訂單狀態
function edit_order_state()
{
    $input = get_json_input();
    if (isset($input["id"], $input["listState"])) {
        $p_id = trim($input["id"]);
        $p_listState = trim($input["listState"]);

        if ($p_id && $p_listState) {
            $conn = create_connection();
            $stmt = $conn->prepare("UPDATE orderlist SET listState = ? WHERE id = ?");
            $stmt->bind_param("si", $p_listState, $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "訂單狀態更新成功");
                } else {
                    respond(false, "訂單狀態未變更");
                }
            } else {
                respond(false, "訂單狀態更新失敗");
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

function delete_order()
{
    $input = get_json_input();
    if (isset($input["id"])) {
        $p_id = trim($input["id"]);
        if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM orderlist WHERE ID = ?");
            $stmt->bind_param("i", $p_id); //一定要傳遞變數

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "訂單刪除成功");
                } else {
                    respond(false, "訂單刪除失敗，並無刪除行為!");
                }
            } else {
                respond(false, "訂單刪除失敗");
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

function count_doneOrder_month()
{
    $year = $_GET['year'] ?? '';
    $conn = create_connection();

    // 使用提供的年份生成報表，查詢該年份每個月的已完成訂單收入總額
    $sql = "SELECT 
                DATE_FORMAT(Create_at, '%Y-%m') AS month, 
                SUM(totalPrice) AS total_revenue 
            FROM orderlist 
            WHERE listState = '已完成' 
            AND YEAR(Create_at) = '$year'
            GROUP BY month
            ORDER BY month";

    $result = $conn->query($sql);

    if ($result) {
        $monthlyReport = [];

        // 將查詢結果存入陣列
        while ($row = $result->fetch_assoc()) {
            $monthlyReport[] = [
                'month' => $row['month'],
                'total_revenue' => $row['total_revenue'] ?? 0  // 如果沒有收入，設為 0
            ];
        }

        respond(true, "每月報表生成成功", $monthlyReport);
    } else {
        respond(false, "每月報表生成失敗: " . $conn->error);
    }

    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'create':
            create_order();
            break;
        case 'editpayment':
            edit_payment_status();
            break;
        case 'editorderstate':
            edit_order_state();
            break;
        default:
            respond(false, '無效的操作');
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getdata':
            get_alldata();
            break;
        case 'yetdata':
            count_yetlist();
            break;
        case 'donedata':
            count_donelist();
            break;
        case 'orderCheck':
            user_order_check($username);
            break;
        case 'ownlist':
            user_own_order($username);
            break;
        case 'monthSUM':
            count_doneOrder_month($year);
            break;

        default:
            respond(false, '無效的操作');
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'deleteOrder':
            delete_order();
            break;
        default:
            respond(false, '無效的操作');
    }
} else {
    respond(false, '無效的請求方法');
}
