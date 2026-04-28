<?php

$interest = htmlspecialchars(input('interest'), ENT_QUOTES, 'UTF-8');
echo "<script>Ossn.researchInterest = " . json_encode($interest) . ";</script>";

echo "
<div class='tone-selector'>
    <div class='tone-title'>Choose conversation tone</div>

    <div class='tone-buttons'>
        <button type='button' class='tone-btn' data-tone='friendly'>Friendly</button>
        <button type='button' class='tone-btn' data-tone='professional'>Professional</button>
        <button type='button' class='tone-btn' data-tone='casual'>Casual</button>
        <button type='button' class='tone-btn' data-tone='icebreaker'>Icebreaker</button>
    </div>

    <div id='conversation-starters'></div>
</div>
";