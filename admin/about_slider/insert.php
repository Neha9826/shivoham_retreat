<?php
// admin/about_slider/insert.php
include '../session.php';
include '../db.php';

// support both insert (multiple files) and edit of single slider if edit_id provided.
$sliderDir = '../uploads/about_slider/';
if(!is_dir($sliderDir)) mkdir($sliderDir, 0777, true);

if(isset($_POST['edit_id']) && intval($_POST['edit_id'])){
    // update single slider record by id - use first uploaded file as replacement
    $edit_id = intval($_POST['edit_id']);
    if(!empty($_FILES['slider_images']['tmp_name'][0])){
        $file = $_FILES['slider_images'];
        $name = time().'_'.basename($file['name'][0]);
        $target = 'uploads/about_slider/'.$name;
        if(move_uploaded_file($file['tmp_name'][0], '../'.$target)){
            // delete old file
            $old = mysqli_fetch_assoc(mysqli_query($conn,"SELECT image FROM about_slider WHERE id=$edit_id"));
            if($old && !empty($old['image']) && file_exists('../'.$old['image'])) @unlink('../'.$old['image']);
            $stmt = $conn->prepare("UPDATE about_slider SET image=? WHERE id=?");
            $stmt->bind_param('si', $target, $edit_id);
            $stmt->execute();
        }
    }
    header('Location: ../addAbout.php#section-slider');
    exit;
} else {
    // insert multiple
    if(!empty($_FILES['slider_images']['tmp_name'])){
        foreach($_FILES['slider_images']['tmp_name'] as $k => $tmp){
            if(empty($tmp)) continue;
            $name = time().'_'.basename($_FILES['slider_images']['name'][$k]);
            $target = 'uploads/about_slider/'.$name;
            if(move_uploaded_file($tmp, '../'.$target)){
                $stmt = $conn->prepare("INSERT INTO about_slider (image) VALUES (?)");
                $stmt->bind_param('s', $target);
                $stmt->execute();
            }
        }
    }
    header('Location: ../addAbout.php#section-slider');
    exit;
}
