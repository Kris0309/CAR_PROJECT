<?php

$servername = "";
$username = "";
$password = "";
$dbname = "";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("連線錯誤" . mysqli_connect_error());
}

if (isset($_FILES['file']['name']) && $_FILES['file']['name'] != "" && isset($_POST["id"]) && $_POST["id"] != "") {
    if ($_FILES['file']['type'] == 'image/jpeg' || $_FILES['file']['type'] == 'image/png') {
        $filename = date("YmdHis") . "_" . $_FILES['file']['name'];
        $id = $_POST["id"];

        $location = "upload/" . $filename;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $location)) {
            $datainfo = array();
            $datainfo["state"] = true;
            $datainfo["message"] = '檔案上傳成功';
            $datainfo["name"] = $_FILES['file']['name'];
            $datainfo["location"] = $location;
            $datainfo["type"] = $_FILES['file']['type'];
            $datainfo["tmp_name"] = $_FILES['file'];
            ['tmp_name'];
            $datainfo["size"] = $_FILES['file']['size'];
            $datainfo["error"] = $_FILES['file']['error'];
            echo json_encode($datainfo);

            $stmt = $conn->prepare("UPDATE car SET Photo = ? WHERE id = ?");
            $stmt->bind_param("si", $filename, $id);

            $stmt->execute();
            $stmt->close();
            $conn->close();
        } else {
            $errorinfo = array();
            $errorinfo["state"] = false;
            $errorinfo["message"] = '檔案上傳失敗';
            $errorinfo["error"] = $_FILES['file']['error'];
            echo json_encode($errorinfo);
        }
    } else {
        echo '{"state":false,"message":"檔案須為jpeg或png"}';
    }
} else {
    echo '{"state":false,"message":"檔案不存在"}';
}
