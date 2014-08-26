<?php
/**
 * Created by PhpStorm.
 * User: murat.kader
 * Date: 26.08.2014
 * Time: 12:23
 */


class KairosApi
{
    const APIID ="d839f656";
    const APIKEY ="feba8edfa1632369de3ab9983fe91f67";
    const APIURL ="http://api.kairos.com/";



    public function enroll($image,$galleryName,$subjectId,$selector="FACE",$symmetricFill=false)
    {
        $dataArray = array(
            "url"=>$image,
            "subject_id" => $subjectId,
            "gallery_name" => $galleryName,
            "selector" => $selector,
            "symmetricFill" => $symmetricFill
        );

        return $this->send($dataArray,"enroll");
    }


    public function recognize($image,$galleryName,$selector="FACE",$maxNumResult=1,$threshold=1,$symmetricFill=false)
    {
        $dataArray = array(
            "url"=>$image,
            "gallery_name" => $galleryName,
            "threshold" => $threshold,
            "max_num_results" => $maxNumResult,
            "selector" => $selector,
            "symmetricFill" => $symmetricFill
        );


        return $this->send($dataArray,"recognize");
    }




    private function send($dataArray,$function) {

        $closeSslVerify =true;
        $url = self::APIURL;
        $type = 'POST';
        $dataString = json_encode($dataArray);

        $options = array(
            CURLOPT_URL           => self::APIURL.$function,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => ($type == 'POST'),
            CURLOPT_POSTFIELDS     => ($type == 'POST') ? $dataString : null,
            CURLOPT_HTTPHEADER => array(
                "app_id:".self::APIID,
                "app_key: ".self::APIKEY,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dataString)
            )
        );

        if($closeSslVerify){
            $options[CURLOPT_SSL_VERIFYHOST] = false;
            $options[CURLOPT_SSL_VERIFYPEER] = false;
        }
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            throw new Exception('SYSERR-Failed to connect to ' . $url . ' Error:' . $error." res:".$result);
        }
        curl_close($ch);

        return json_decode($result);
    }


    public function test()
    {
        $kairos = new KairosApi();
        $res = $kairos->enroll("https://lcdn3.lmng.net/imgres/29544/products/7344444/305/1.jpg?v5","gallery1","testSubject2");
        var_dump($res);

        $res2 = $kairos->recognize("https://lcdn3.lmng.net/imgres/29544/products/7344444/305/1.jpg?v5","gallery1");
        var_dump($res2);
    }
}

function uploadImageToWeb($imageData)
{
    $client_id = '634b96c9786f8d4';
    //ba214c53559d1177c1a5f1db11c01ecf8a0efb1e
    $url = 'https://api.imgur.com/3/image.json';

    $imageData =$imageData;
    $imageData = str_replace('data:image/png;base64,', '', $imageData);

    $headers = array("Authorization: Client-ID $client_id");
    $pvars  = array('image' => ($imageData));

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL=> $url,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $pvars
    ));

    $json_returned = curl_exec($curl); // blank response
    return json_decode($json_returned);
}
/*
uploadImageToWeb(
""
);exit;
*/

if(isset($_POST['create']) && $_POST['imageHash'] && $_POST['imageName'])
{
    $kairos = new KairosApi();

    $imgRumRes= uploadImageToWeb($_POST['imageHash']);

    $res = $kairos->enroll($imgRumRes->data->link,"gen2",$_POST['imageName']);

    $message = "";

    if($res->images[0]->status == "failure")
    {
        $message .= json_encode($res);
        $message .= $res->images[0]->message;
    }else{
        $message .=  $res->images[0]->subject_id . " Great ! ->".$imgRumRes->data->link;
    }
}

if(isset($_POST['recognize']))
{
    $kairos = new KairosApi();
    $imgRumRes= uploadImageToWeb($_POST['imageHash']);

    $res = $kairos->recognize($imgRumRes->data->link,"gen2");


    if($res->images[0]->transaction->status == "success")
    {
        $message.= "I Know You You are  --- ".$res->images[0]->transaction->subject. " ---- ";
    }else{
        $message= "sorry !";
    }
}

?>

<html>
<head>
    <title>HTML 5 camera</title>

    <style>
        body{font-family:Sans-Serif;}
        canvas{position:absolute; left: -9999em;}
        #button, #recognize{background-color: Red; color: #fff; padding: 3px 10px; cursor:pointer; display: inline-block; border-radius: 5px;}
        #sidebar{float:right; width: 45%;}
        #main{float:left; width: 45%;}
        #imageToForm{width:100%;}
        #preview{margin: 20px 0;}
        label{display: block;}
    </style>
</head>
<body>
<form method="POST">
<div id="main">
    <video id="video"></video>


</div>

<div id="sidebar">
    <h1>Register</h1>
    <a id="button">Take A Picture</a>
    <!-- target for the canvas-->
    <div id="canvasHolder"></div>
    <!--preview image captured from canvas-->
    <img id="preview" src="http://www.clker.com/cliparts/A/Y/O/m/o/N/placeholder-hi.png" width="160" height="120" />

        <label>base64 image:</label>
        <input id="imageToForm" type="text" name="imageHash" />
        <input type="text" name="imageName" value="" placeholder="Who is That ?">
        <input type="submit" name="create" value="create">
        <input type="submit" name="recognize" value="recognize">

    <?if(isset($message)){?>
        <h1><?=$message?></h1>
    <?}?>

</div>
</form>
<script>

    var video;
    var dataURL;

    //http://coderthoughts.blogspot.co.uk/2013/03/html5-video-fun.html - thanks :)
    function setup() {
        navigator.myGetMedia = (navigator.getUserMedia ||
            navigator.webkitGetUserMedia ||
            navigator.mozGetUserMedia ||
            navigator.msGetUserMedia);
        navigator.myGetMedia({ video: true }, connect, error);
    }

    function connect(stream) {
        video = document.getElementById("video");
        video.src = window.URL ? window.URL.createObjectURL(stream) : stream;
        video.play();
    }

    function error(e) { console.log(e); }

    addEventListener("load", setup);

    function captureImage() {
        var canvas = document.createElement('canvas');
        canvas.id = 'hiddenCanvas';
        //add canvas to the body element
        document.body.appendChild(canvas);
        //add canvas to #canvasHolder
        document.getElementById('canvasHolder').appendChild(canvas);
        var ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth / 4;
        canvas.height = video.videoHeight / 4;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        //save canvas image as data url
        dataURL = canvas.toDataURL();
        //set preview image src to dataURL
        document.getElementById('preview').src = dataURL;
        // place the image value in the text box
        document.getElementById('imageToForm').value = dataURL;
    }

    //Bind a click to a button to capture an image from the video stream
    var el = document.getElementById("button");
    el.addEventListener("click", captureImage, false);

</script>

</body>
</html>