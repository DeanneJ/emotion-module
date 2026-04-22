<?php
//changes (LINE 2 - 54)
require_once ossn_route()->www . "configurations/behavior_detection_config.php";

function bg_get_all_users_api() {

    $url = BG_API_BASE . "/behavior/users";
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        return false; // API failed
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return false;
    }
    return $data;
}

function bg_get_user_explanation($user_id) {

    $url = BG_API_BASE . "/behavior/user/" . intval($user_id) . "/explanation";
    $response = @file_get_contents($url);
    if ($response === FALSE) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return null;
    }
    return $data;
}


function bg_get_user_restrictions($user_id){

    $url = BG_API_BASE . "/behavior/user/" . intval($user_id) . "/restrictions";

    $response = @file_get_contents($url);

    if($response === FALSE){
        return [];
    }

    $data = json_decode($response, true);

    if(!is_array($data)){
        return [];
    }

    return $data;
}

/**
 * Open Source Social Network
 *
 * @package   Open Source Social Network (OSSN)
 * @author    OSSN Core Team <info@openteknik.com>
 * @copyright (C) OpenTeknik LLC
 * @license   Open Source Social Network License (OSSN LICENSE)  http://www.opensource-socialnetwork.org/licence
 * @link      https://www.opensource-socialnetwork.org/
 */
 
 $users = new OssnUser;
 $genders = $users->genderTypes();

 $total = array();
 $online = array();
 foreach($genders as $gender) {
		$total[]	= false;
		$online[]	= false;
 }
 foreach($total as $k => $t){
		if($t === false){
			$total[$k] = 0;	
		}
 }
 foreach($online as $k => $o){
		if($o === false){
			$online[$k] = 0;	
		}
 }
 $flush_cache = ossn_site_url("action/admin/cache/flush", true);
?>
<div class="ossn-admin-dashboard">
        <div class="row main-analytics-row">
            <div class="col-lg-8">
                <div class="graph-card-main">
                    <div class="admin-dashboard-title"><?php echo ossn_print("users");?></div>
                    <hr class="my-3 opacity-25">
                    <div class="admin-dashboard-contents">
                        <canvas id="users-count-graph" height="300"></canvas>
                        <div id="usercount-lineLegend"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 d-flex flex-column">
                <div class="pie-mini-card">
                    <div class="admin-dashboard-title"><?php echo ossn_print("users");?>  (<span id="users-classified-graph-total">---</span>)</div>
                    <div class="admin-dashboard-contents mt-2" style="flex-grow:1;">
                        <div class="ossn-loading users-classified-graph-loader mx-auto mt-5"></div>
                        <canvas id="users-classified-graph" class="d-none" height="150"></canvas>
                    </div>
                </div>
                <div class="pie-mini-card" style="margin-bottom:0;">
                    <div class="admin-dashboard-title"><?php echo ossn_print("online:users");?> (<span id="onlineusers-classified-graph-total">---</span>)</div>
                    <div class="admin-dashboard-contents mt-2" style="flex-grow:1;">
                        <div class="ossn-loading onlineusers-classified-graph-loader mx-auto mt-5"></div>
                        <canvas id="onlineusers-classified-graph" class="d-none" height="150"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="icon-wrap bg-blue"><i class="fa-solid fa-cubes"></i></div>
                    <div>
                        <div class="admin-dashboard-title"><?php echo ossn_print('components'); ?></div>
                        <div class="card-value"><?php echo ossn_total_components(); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="icon-wrap bg-purple"><i class="fa-solid fa-palette"></i></div>
                    <div>
                        <div class="admin-dashboard-title"><?php echo ossn_print('themes'); ?></div>
                        <div class="card-value"><?php echo ossn_site_total_themes(); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="icon-wrap bg-red"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <div class="admin-dashboard-title"><?php echo ossn_print("admin:users:unvalidated");?></div>
                        <div id="admin-dashboard-unvalidated-text" class="card-value"><div class="ossn-loading"></div></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="icon-wrap bg-orange"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                    <div>
                        <div class="admin-dashboard-title"><?php echo ossn_print('available:updates'); ?></div>
                        <div class="avaiable-updates"><div class="loading-version ossn-loading"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="icon-wrap bg-indigo"><i class="fa-solid fa-code"></i></div>
                    <div>
                        <div class="admin-dashboard-title"><?php echo ossn_print('my:version'); ?></div>
                        <div class="card-value"><?php echo ossn_site_settings('site_version'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="icon-wrap bg-cyan"><i class="fa-solid fa-file-code"></i></div>
                    <div>
                        <div class="admin-dashboard-title"><?php echo ossn_print('my:files:version'); ?></div>
                        <div class="card-value"><?php echo ossn_package_information()->version; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="cache-action-card">
                    <div class="admin-dashboard-title"><?php echo ossn_print('admin:cache'); ?></div>
                    <a href="<?php echo $flush_cache;?>" class="btn-flush-dark"><?php echo ossn_print('admin:flush:cache'); ?></a>
                </div>
            </div>
        </div>
</div>

<!-- changes 183-913 -->
<?php





if(isset($_POST['bg_action_mode'])){

    $mode = $_POST['bg_action_mode'];
    $user_id = isset($_POST['bg_user_id']) ? intval($_POST['bg_user_id']) : 0;
    $action = isset($_POST['bg_action']) ? trim($_POST['bg_action']) : "";

    $action_key = "";

    if($action == "LIMIT_POSTING"){
        $action_key = "limit_posting";
    }

    if($action == "LIMIT_COMMENTS"){
        $action_key = "limit_comments";
    }

    if(empty($user_id) || empty($action_key)){
        echo json_encode([
            "status" => "error",
            "message" => "Invalid moderation action."
        ]);
        exit;
    }

    $api_url = BG_API_BASE . "/behavior/moderation";

    $payload = json_encode([
        "user_id" => $user_id,
        "action" => $action_key,
        "mode" => $mode
    ]);

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json\r\n",
            "method"  => "POST",
            "content" => $payload
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($api_url, false, $context);

    if($response === FALSE){
        echo json_encode([
            "status" => "error",
            "message" => "API call failed."
        ]);
        exit;
    }

    $decoded = json_decode($response, true);

    if(is_array($decoded) && isset($decoded["status"]) && $decoded["status"] === "success"){
        echo json_encode([
            "status" => "success",
            "message" => "Moderation action updated successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "API returned an invalid response."
        ]);
    }
    exit;
}

$high = 0;
$critical = 0;
$medium = 0;
$normal = 0;
$new_accounts = 0;

$data = [];


$data = bg_get_all_users_api();

if($data === false){
    echo "<p style='color:red;'>⚠ BehaviourGuard API not reachable</p>";
    return;
}

/* RESET COUNTERS */
$high = 0;
$critical = 0;
$medium = 0;
$normal = 0;
$new_accounts = 0;

/* COUNT LOGIC */
foreach($data as $row){

    $risk_level = $row['risk_level'];

    if($risk_level == "HIGH"){
        $high++;
    }

    if($risk_level == "CRITICAL"){
        $critical++;
    }

    if($risk_level == "MEDIUM"){
        $medium++;
    }

    if($risk_level == "LOW"){
        $normal++;
    }

    if($risk_level == "INSUFFICIENT_DATA"){
        $new_accounts++;
    }
}

?>

<div class="row mt-4">

<div class="col-lg-4 col-md-6">

<div class="metric-card" style="cursor:pointer;" onclick="toggleBotPanel()">

<div class="icon-wrap bg-red">
<i class="fa-solid fa-robot"></i>
</div>

<div>

<div class="admin-dashboard-title">
<b>Account Risk Detection</b>
</div>

<div class="card-value">
<?php echo $high; ?> High Risk
</div>

<small><?php echo $critical; ?> Critical Users</small>

</div>

</div>

</div>

</div>


<!-- Hidden Panel -->
<div id="bot-panel" style="display:none;margin-top:20px;">

<h4 class="admin-dashboard-title" style="text-align:center;">Detection Results</h4>

<div class="row mt-3 mb-3">


<div class="col-lg-4 col-md-4">
<div class="bg-card">
<div class="icon-large">
<i class="fa-solid fa-triangle-exclamation"></i>
</div>
<div class="metric-text">
<div class="admin-dashboard-title"><b>CRITICAL USERS</b></div>
<div class="card-value"><?php echo $critical; ?></div>
</div>
</div>
</div>


<div class="col-lg-4 col-md-4">
<div class="bg-card">
<div class="icon-large">
<i class="fa-solid fa-user-shield"></i>
</div>
<div class="metric-text">
<div class="admin-dashboard-title"><b>HIGH RISK USERS</b></div>
<div class="card-value"><?php echo $high; ?></div>
</div>
</div>
</div>


<div class="col-lg-4 col-md-4">
<div class="bg-card">
<div class="icon-large">
<i class="fa-solid fa-chart-line"></i>
</div>
<div class="metric-text">
<div class="admin-dashboard-title"><b>MEDIUM RISK USERS</b></div>
<div class="card-value"><?php echo $medium; ?></div>
</div>
</div>
</div>




</div>


<div class="d-flex justify-content-between align-items-center mt-4 mb-3 flex-wrap" style="gap:10px;">

    <div>
        <label for="riskFilter" style="font-weight:600;margin-right:10px;">Show:</label>
        <select id="riskFilter" class="form-control" style="width:220px;display:inline-block;">

            <option value="suspicious" selected>🕵️ Suspicious</option>
            <option value="CRITICAL">🚨 Critical</option>
            <option value="HIGH">⚠️ High Risk</option>
            <option value="MEDIUM">🟡 Medium Risk</option>
            <option value="LOW">✅ Normal Users</option>
            <option value="INSUFFICIENT_DATA">🆕 New Accounts</option>
            <option value="all">👥 All Users</option>

        </select>
    </div>

</div>



<div class="ossn-admin-all-users mt-3">
<div class="table-responsive">

<table class="table ossn-users-list">

<thead>
<tr class="table-titles">
<th>User ID</th>
<th>Risk Level</th>
<th>Risk Score</th>
<th>Investigate</th>
</tr>
</thead>

<tbody>

<?php

if(!empty($data)){

foreach($data as $row){

$user = $row['user_id'];
$risk = $row['risk_level'];
$score = $row['risk_score'];



echo "<tr class='risk-row' data-risk='" . htmlspecialchars($risk) . "'>";

echo "<td>" . htmlspecialchars($user) . "</td>";

if($risk == "HIGH"){
echo "<td><span class='badge' style='background:#f97316;color:white;'>HIGH</span></td>";
}

if($risk == "CRITICAL"){
echo "<td><span class='badge' style='background:#ef4444;color:white;'>CRITICAL</span></td>";
}

if($risk == "MEDIUM"){
echo "<td><span class='badge' style='background:#facc15;color:black;'>MEDIUM</span></td>";
}

if($risk == "LOW"){
echo "<td><span class='badge' style='background:#10b981;color:white;'>NORMAL</span></td>";
}

if($risk == "INSUFFICIENT_DATA"){
echo "<td><span class='badge' style='background:#6b7280;color:white;'>NEW ACCOUNT</span></td>";
}



echo "<td>" . round((float)$score, 2) . "</td>";

echo "<td>
<button class='btn btn-sm btn-dark' onclick='toggleUserPanel(" . (int)$user . ")'>
View
</button>
</td>";

echo "</tr>";


$user_explain = bg_get_user_explanation($user);


echo "<tr id='user-panel-$user' class='detail-row' data-risk='" . htmlspecialchars($risk) . "' style='display:none;background:#f9fafb;'>";
echo "<td colspan='4'>";
echo "<div style='padding:20px;'>";
echo "<h5>Behavioural Investigation — User $user</h5>";

$evidence = 0;

if($user_explain && isset($user_explain['evidence_score'])){
    $evidence = floatval($user_explain['evidence_score']);
}

if($evidence < 0.4){

echo "
<div style='margin:10px 0;padding:10px;border-radius:6px;background:#e0f2fe;border:1px solid #7dd3fc;'>
<b>🔵 Monitoring Phase — New Account</b>
<p style='margin:5px 0;font-size:13px;'>
BehaviourGuard has limited behavioural evidence for this account.
Detection is based on early behavioural patterns.
Moderation decisions should be applied cautiously.
</p>
</div>
";

}

elseif($evidence < 0.7){

echo "
<div style='margin:10px 0;padding:10px;border-radius:6px;background:#fef3c7;border:1px solid #fbbf24;'>
<b>🟡 Behaviour Under Observation</b>
<p style='margin:5px 0;font-size:13px;'>
Moderate behavioural evidence is available.
The system is combining early and mature behavioural analysis.
</p>
</div>
";

}

else{

echo "
<div style='margin:10px 0;padding:10px;border-radius:6px;background:#dcfce7;border:1px solid #22c55e;'>
<b>🟢 Established Behaviour Profile</b>
<p style='margin:5px 0;font-size:13px;'>
Sufficient behavioural evidence exists.
The risk score is based primarily on mature behavioural analysis.
Moderation decisions can be made with higher confidence.
</p>
</div>
";

}


echo "<div style='display:flex;gap:40px;flex-wrap:wrap;'>";

echo "<div style='flex:1;min-width:300px;'>";
echo "<b>Risk Trend</b>";
echo "<div style='margin-top:10px'>";
echo "<canvas id='riskChart-$user' height='140'></canvas>";
echo "</div>";
echo "<div id='riskInsight-$user' style='margin-top:10px;font-size:13px;color:#555;'></div>";
echo "</div>";

echo "<div style='flex:1;min-width:300px;'>";
echo "<b>Explainability</b>";

if($user_explain && !empty($user_explain['reasons'])){
    echo "<ul>";
    foreach($user_explain['reasons'] as $reason){
        echo "<li>" . htmlspecialchars($reason) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No explanation available.</p>";
}


echo "<hr style='margin-top:15px;'>";

echo "<b>Suggested Moderation Actions</b>";

if($user_explain && !empty($user_explain['actions'])){

    echo "<div style='margin-top:10px;'>";

    foreach($user_explain['actions'] as $action){

        $act = htmlspecialchars($action['action']);

        $action_key = "";

        if($act == "LIMIT_POSTING"){
            $action_key = "limit_posting";
        }

        if($act == "LIMIT_COMMENTS"){
            $action_key = "limit_comments";
        }

$user_restrictions = bg_get_user_restrictions($user);

$already_applied = false;

if(!empty($action_key)){
    $already_applied = isset($user_restrictions[$action_key]) && $user_restrictions[$action_key] === true;
}

        $reason = htmlspecialchars($action['reason']);
        $enforceable = $action['enforceable'];

        echo "<div style='border:1px solid #e5e7eb;padding:10px;margin-bottom:10px;border-radius:6px;'>";

        echo "<b>$act</b>";

        echo "<p style='margin:5px 0;color:#555;font-size:13px;'>$reason</p>";

        echo "<div style='display:flex;gap:10px;'>";

        if($enforceable){

            if($already_applied){

                echo "<button class='btn btn-sm btn-warning'
                onclick='disableModeration($user,\"$act\")'>
                Disable
                </button>";

            }else{

                echo "<button class='btn btn-sm btn-success'
                onclick='applyModeration($user,\"$act\")'>
                Apply
                </button>";

            }

        }else{

            echo "<button class='btn btn-sm btn-secondary'
            onclick='recordModeration($user,\"$act\")'>
            Apply
            </button>";

        }

        echo "<button class='btn btn-sm btn-light'
        onclick='ignoreModeration(this)'>
        Ignore
        </button>";

        echo "</div>";

        echo "</div>";
    }

    echo "</div>";

}else{

    echo "<p style='color:#888;margin-top:5px;'>No moderation actions recommended.</p>";

}



echo "</div>";

echo "</div>";
echo "</div>";
echo "</td>";
echo "</tr>";

}

}

?>

</tbody>
</table>




</div>

</div>
</div>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const BG_API_BASE = "<?php echo BG_API_BASE; ?>";
</script>

<script>

function toggleBotPanel(){

var panel = document.getElementById("bot-panel");

if(panel.style.display === "none"){
panel.style.display = "block";
}else{
panel.style.display = "none";
}

}

function toggleUserPanel(user){

    var panel = document.getElementById("user-panel-"+user);

    if(panel.style.display === "none"){
        panel.style.display = "table-row";

        // FORCE reflow before chart
        setTimeout(function(){
            requestAnimationFrame(() => {
                loadUserChart(user);
            });
        }, 100);

    }else{
        panel.style.display = "none";
    }

}


function applyModeration(user,action){

    $.post(window.location.href,{
        bg_action_mode:"apply",
        bg_user_id:user,
        bg_action:action
    },function(){

        alert("Moderation applied successfully");
        location.reload();

    });

}

function recordModeration(user,action){

    alert("Moderation action recorded: "+action);

}

function ignoreModeration(btn){

    var card = btn.closest("div");
    card.style.display="none";

}

function disableModeration(user,action){

    $.post(window.location.href,{
        bg_action_mode:"disable",
        bg_user_id:user,
        bg_action:action
    },function(){

        alert("Moderation disabled successfully");
        location.reload();

    });

}

function loadUserChart(user){

    if(window["chart_"+user]){
        window["chart_"+user].destroy();
    }

    fetch(BG_API_BASE + "/behavior/user/" + user + "/history")
    .then(res => res.json())
    .then(data => {

        console.log("Chart data:", data); 

        if(data.length === 0) return;

        const labels = data.map((_, i) => "T" + (i + 1));

        const scores = data.map(x => Number(x.risk_score));

        const canvas = document.getElementById("riskChart-"+user);

        if(!canvas){
            console.log("Canvas not found for user:", user);
        return;
        }

        const ctx = canvas.getContext("2d");

        const latestScore = scores.length ? scores[scores.length - 1] : 0;

        let lineColor = "#10b981"; // LOW = green
        if (latestScore >= 85) {
            lineColor = "#ef4444"; // CRITICAL = red
        } else if (latestScore >= 70) {
            lineColor = "#f97316"; // HIGH = orange
        } else if (latestScore >= 50) {
            lineColor = "#facc15"; // MEDIUM = yellow
        }

        console.log("CTX:", ctx);
        console.log("Labels:", labels);
        console.log("Scores:", scores);

        window["chart_"+user] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Risk Score',
                    data: scores,
                    tension: 0.4,
                    fill: true,
                    borderColor: lineColor,
                    backgroundColor: lineColor + "33",
                    pointBackgroundColor: lineColor,
                    pointBorderColor: lineColor,
                    pointRadius: 4,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 10
                        }
                    }
                }
            }
        });

        // 🔥 ADD INSIGHT
        generateInsight(user, scores);

    });
}

function generateInsight(user, scores){

    let insight = "Stable behaviour";

    if(scores.length >= 2){

        let diff = scores[scores.length - 1] - scores[0];

        if(diff > 10){
            insight = "⚠️ Risk increasing over time";
        }
        else if(diff < -10){
            insight = "✅ Risk decreasing";
        }
    }

    document.getElementById("riskInsight-"+user).innerText = insight;
}


function applyRiskFilter(){

    var selected = document.getElementById("riskFilter").value;

    document.querySelectorAll(".risk-row").forEach(function(row){

        var risk = row.getAttribute("data-risk");
        var userIdCell = row.querySelector("td");
        var detailRow = null;

        if(userIdCell){
            var userId = userIdCell.textContent.trim();
            detailRow = document.getElementById("user-panel-" + userId);
        }

        var showRow = false;

        if(selected === "all"){
            showRow = true;
        } else if(selected === "suspicious"){
            showRow = (risk === "CRITICAL" || risk === "HIGH" || risk === "MEDIUM");
        } else {
            showRow = (risk === selected);
        }

        if(showRow){
            row.style.display = "";
        } else {
            row.style.display = "none";
            if(detailRow){
                detailRow.style.display = "none";
            }
        }

    });

}

document.addEventListener("DOMContentLoaded", function(){

    var riskFilter = document.getElementById("riskFilter");

    if(riskFilter){
        riskFilter.addEventListener("change", applyRiskFilter);
        applyRiskFilter();
    }

});

</script>
<!-- end 1 -->

<script>
$(window).on('load', function () {
	Ossn.PostRequest({
		'url': Ossn.site_url + 'administrator/xhr/unvalidated',
		'callback': function (result) {
				$('#admin-dashboard-unvalidated-text').html(parseInt(result.total));
		}
	});

    //changes (LINE NO 922 - 928)
	/* Auto refresh BehaviourGuard dashboard every 30 minutes */
	setInterval(function(){
		location.reload();
	},1800000);
    // end 2
});
</script>
<style>
body {background:#fdfdfd;}

/* changes  (LINE NO 933 - 979)*/
.metric-card .card-value{
    font-size:32px;
    font-weight:700;
}

.admin-dashboard-title{
    font-size:12px;
    letter-spacing:1px;
    text-transform:uppercase;
    color:#6b7280;
}


.bg-card{
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:22px;
    display:flex;
    align-items:center;
    gap:20px;
    height:100%;
}

.icon-large{
    font-size:32px;
}

.metric-text .admin-dashboard-title{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:1px;
    color:#6b7280;
}

.metric-text .card-value{
    font-size:32px;
    font-weight:700;
}

.icon-large i{
    color:#000;
}
/* end 3 */

</style>
<?php echo ossn_plugin_view('javascripts/dynamic/admin/dashboard/users/users'); ?>
<?php echo ossn_plugin_view('javascripts/dynamic/admin/dashboard/users/classfied', array('genders' => $genders)); ?>
<?php echo ossn_plugin_view('javascripts/dynamic/admin/dashboard/users/online/classfied', array('genders' => $genders)); ?>
