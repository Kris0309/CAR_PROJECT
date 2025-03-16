(function () {
    //選單一定要有以下設定
    // <span class="h4 text-success fw-900 me-3 d-none" id="s02_username_showtext">歡迎會員: <span
    // class="h3 text-danger" id="s02_username_text">XXX</span></span>
    // <button class="btn btn-danger d-none" id="s02_logout_btn">登出</button>
    //w3c
    function getCookie(cname) {
        let name = cname + "=";
        let ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }


    //檢查uid是否存在, 若沒有則導向登入畫面
    if (!getCookie("Uid01")) {
        Swal.fire({
            title: "請先登入會員!",
            showDenyButton: false,
            showCancelButton: false,
            confirmButtonText: "確認",
            denyButtonText: `Don't save`,
            allowOutsideClick: false
        }).then((result) => {
            /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                location.href = "/20250206/index.html";
            }
        });

        return;
    }

    //若uid存在, 傳遞至後端API執行驗證
    var JSONdata = {};
    JSONdata["uid01"] = getCookie("Uid01");
    console.log(JSON.stringify(JSONdata));

    $.ajax({
        type: "POST",
        url: "/20250206/member_control_api_v1.php?action=checkuid",
        data: JSON.stringify(JSONdata),
        dataType: "json",
        success: function (data) {
            if (data.state) {
                //顯示歡迎訊息
                $("#s02_username_showtext").removeClass("d-none");
                $("#s02_username_text").text(data.data.Username);

                //顯示登出按鈕
                $("#s02_logout_btn").removeClass("d-none");
            } else {
                Swal.fire({
                    title: "請先登入會員!",
                    showDenyButton: false,
                    showCancelButton: false,
                    confirmButtonText: "確認",
                    denyButtonText: `Don't save`,
                    allowOutsideClick: false
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        location.href = "/20250206/index.html";
                    }
                });
            }
        },
        error: function () {
            Swal.fire({
                title: "API介接錯誤!",
                text: "/20250206/member_control_api_v1.php?action=checkuid",
                icon: "error"
            });
        }
    });
})();