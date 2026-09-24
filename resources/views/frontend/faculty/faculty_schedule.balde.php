<?php
$faculty_name = "Prof. Elena Santos";
$faculty_dept = "Faculty of IT";
$faculty_initials = "ES";
$semester = "Spring Semester 2026";

$stats = [
    ["label"=>"Total Subjects","value"=>"7"],
    ["label"=>"Total Units","value"=>"21"],
    ["label"=>"This Week","value"=>"18 hrs"],
];

$days = ["Mon","Tue","Wed","Thu","Fri"];
$active_day = "Mon";

$schedule_by_day = [
    "Mon" => [
        ["start"=>"08:00","end"=>"10:00","subject"=>"Web Development","code"=>"IT-301","section"=>"BSIT 3A","room"=>"Room 101","type"=>"Lab"],
        ["start"=>"10:30","end"=>"12:30","subject"=>"Database Systems","code"=>"IT-302","section"=>"BSIT 3B","room"=>"Lab 105","type"=>"Lab"],
    ]
];

$active_classes = $schedule_by_day[$active_day] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SmartRoom Schedule</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box}

/* ===== KEEP YOUR SIDEBAR DESIGN ===== */
.sidebar{
    position:fixed;
    width:240px;
    height:100vh;
    background:#0f2044;
    color:#fff;
    padding:20px;
}

/* ===== IMPROVED MAIN DESIGN ===== */
body{
    font-family:'DM Sans',sans-serif;
    background:#f5f7fb;
    color:#1a2540;
}

.main{
    margin-left:240px;
    padding:30px;
}

/* TOPBAR */
.topbar{
    background:#fff;
    padding:16px 24px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 14px rgba(0,0,0,0.05);
    margin-bottom:20px;
}

/* HEADER */
.page-header{
    margin-bottom:20px;
}

.page-header h1{
    font-size:26px;
    font-weight:700;
}

.page-header p{
    color:#6b7a99;
    font-size:14px;
}

/* BUTTONS */
.btn{
    padding:10px 16px;
    border:none;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
    transition:0.2s;
}

.btn-primary{
    background:linear-gradient(135deg,#1e3a5f,#2c4a7a);
    color:#fff;
}

.btn-outline{
    background:#fff;
    border:1px solid #e0e6f2;
}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:15px;
    margin-bottom:25px;
}

.stat{
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 6px 16px rgba(0,0,0,0.05);
}

.stat h3{
    font-size:12px;
    color:#6b7a99;
}

.stat p{
    font-size:24px;
    font-weight:700;
}

/* CARDS */
.card{
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
    margin-bottom:15px;
    transition:0.2s;
}

.card:hover{
    transform:translateY(-3px);
}

.class-title{
    font-weight:700;
    margin-bottom:6px;
}

.class-meta{
    font-size:13px;
    color:#6b7a99;
}

/* RESPONSIVE */
@media(max-width:768px){
    .stats{
        grid-template-columns:1fr;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR (UNCHANGED STRUCTURE) -->
<div class="sidebar">
    <h2>SmartRoom</h2>
    <p><?= $faculty_name ?></p>
</div>

<div class="main">

    <div class="topbar">
        <div>Dashboard</div>
        <button class="btn btn-primary">Add Schedule</button>
    </div>

    <div class="page-header">
        <h1><?= $faculty_name ?> Schedule</h1>
        <p><?= $semester ?></p>
    </div>

    <!-- STATS -->
    <div class="stats">
        <?php foreach($stats as $s): ?>
        <div class="stat">
            <h3><?= $s['label'] ?></h3>
            <p><?= $s['value'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- CLASSES -->
    <?php foreach($active_classes as $c): ?>
    <div class="card">
        <div class="class-title"><?= $c['subject'] ?> (<?= $c['code'] ?>)</div>
        <div class="class-meta">
            <?= $c['start'] ?> - <?= $c['end'] ?> • <?= $c['room'] ?> • <?= $c['section'] ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

</body>
</html>