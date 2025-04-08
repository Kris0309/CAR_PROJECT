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

function register_user()
{
    $input = get_json_input();
    if (isset($input["Username"], $input["Password"], $input["Email"], $input["Phone"],)) {
        $p_username = $input["Username"];
        $p_password = password_hash(trim($input["Password"]), PASSWORD_DEFAULT);
        $p_email    = trim($input["Email"]);
        $p_phone    = trim($input["Phone"]);
        if ($p_username && $p_password && $p_email && $p_phone) {
            $conn = create_connection();

            $stmt = $conn->prepare("INSERT INTO user(Username, Password, Email, Phone) VALUES(?, ?, ?, ?)");
            $stmt->bind_param("ssss", $p_username, $p_password, $p_email, $p_phone);

            if ($stmt->execute()) {
                respond(true, "註冊成功");
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

function login_user(){
    $input = get_json_input();
    if(isset($input["Username"], $input["Password"])){
        $p_username = trim($input["Username"]);
        $p_password = trim($input["Password"]);
        if($p_username && $p_password){
            $conn = create_connection();
        
            $stmt = $conn->prepare("SELECT * FROM user WHERE Username = ?");
            $stmt->bind_param("s", $p_username);
            $stmt->execute();
            $result = $stmt->get_result();
           
            if($result->num_rows === 1){
                $row = $result->fetch_assoc();
                if(password_verify($p_password, $row["Password"])){
                    $uid01 = substr(hash('sha256', time()), 10, 4) . substr(bin2hex(random_bytes(16)), 4, 4);
                    $update_stmt = $conn->prepare("UPDATE user SET Uid01 = ? WHERE Username = ?");
                    $update_stmt->bind_param('ss', $uid01, $p_username);
                    if($update_stmt->execute()){
                        $user_stmt = $conn->prepare("SELECT Username, Email, Uid01, Phone, Role, Created_at FROM user WHERE Username = ?");
                        $user_stmt->bind_param("s", $p_username); //一定要傳遞變數
                        $user_stmt->execute();
                        $user_data = $user_stmt->get_result()->fetch_assoc();
                        respond(true, "登入成功", $user_data);                          
                    }else{
                        respond(false, "登入失敗, UID更新失敗");  
                    }
                }else{
                    respond(false, "登入失敗, 密碼錯誤");
                }
            }else{
                respond(false, "登入失敗, 該帳號不存在");
            }
            $stmt->close();
            $conn->close();
        }else{
            respond(false, "登入失敗, 欄位不得為空");
        }
    }else{
        respond(false, "登入失敗, 欄位錯誤");
    }
}

function check_uid()
{
    $input = get_json_input();
    if (isset($input["uid01"])) {
        $p_uid = trim($input["uid01"]);
        if ($p_uid) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT Username, Email, Uid01, Phone, Role, Created_at FROM user WHERE Uid01 = ?");
            $stmt->bind_param("s", $p_uid); 
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $userdata = $result->fetch_assoc();
                respond(true, "驗證成功", $userdata);
            } else {
                respond(false, "驗證失敗");
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

function check_uni_username()
{
    $input = get_json_input();
    if (isset($input["Username"])) {
        $p_username = trim($input["Username"]);
        if ($p_username) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT Username FROM user WHERE Username = ?");
            $stmt->bind_param("s", $p_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                respond(false, "帳號已存在, 不可以使用");
            } else {
                respond(true, "帳號不存在, 可以使用");
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

function check_uni_Phone()
{
    $input = get_json_input();
    if (isset($input["Phone"])) {
        $p_phone = trim($input["Phone"]);
        
        if ($p_phone) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT Phone FROM user WHERE Phone = ?");
            $stmt->bind_param("s", $p_phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                respond(false, "手機號碼有誤");
            } else {
                respond(true, "正確格式，可以使用");
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

function get_all_user_data()
{
    $conn = create_connection();

    $stmt = $conn->prepare("SELECT * FROM user ORDER BY Role DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = array();
        while ($row = $result->fetch_assoc()) {
            unset($row["Password"]);
            unset($row["Uid01"]);
            $mydata[] = $row;
        }
        respond(true, "取得所有會員資料成功", $mydata);
    } else {
        respond(false, "查無資料");
    }
    $stmt->close();
    $conn->close();
}

function count_user()
{
    $conn = create_connection();

    $sql = "SELECT count(*) as count_member FROM user";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        respond(true, "會員人數統計成功", $row);
    } else {
        respond(false, "會員人數統計失敗與相關錯誤訊息");
    }

    $conn->close();
}

function update_user()
{
    $input = get_json_input();
    if (isset($input["id"], $input["Email"], $input["Phone"])) {
        $p_id = trim($input["id"]);
        $p_email = trim($input["Email"]);
        $p_phone = trim($input["Phone"]);
        if ($p_id && $p_email && $p_phone) {
            $conn = create_connection();

            $stmt = $conn->prepare("UPDATE user SET Email = ?, Phone = ? WHERE ID = ?");
            $stmt->bind_param("ssi", $p_email, $p_phone, $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "會員更新成功");
                } else {
                    respond(false, "會員更新失敗，並無更新行為!");
                }
            } else {
                respond(false, "會員更新失敗");
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

function delete_user()
{
    $input = get_json_input();
    if (isset($input["id"])) {
        $p_id = trim($input["id"]);
        if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM user WHERE ID = ?");
            $stmt->bind_param("i", $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "會員刪除成功");
                } else {
                    respond(false, "會員刪除失敗，並無刪除行為!");
                }
            } else {
                respond(false, "會員刪除失敗");
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
        case 'register':
            register_user();
            break;

        case 'login':
            login_user();
            break;

        case 'checkuid':
            check_uid();
            break;

        case 'checkuni':
            check_uni_username();
            break;

        case 'checkphone':
            check_uni_Phone();
            break;

        case 'update':
            update_user();
            break;

        default:
            respond(false, "無效的操作");;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getalldata':
            get_all_user_data();
            break;
        case 'count':
            count_user();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'delete':
            delete_user();
            break;

        default:
            respond(false, "無效的操作");
    }
} else {
    respond(false, "無效的請求方法");
}
