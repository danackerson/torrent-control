<?php
$db = new SQLiteDatabase('db/show-history.sqlite', 0666, $error);

$delete = $_GET["delete"];
if ($delete) {
    $result = $db->queryexec("DELETE FROM show_history WHERE Id = ".$delete);
}
$clean = $_GET["clean"];
if ($clean) {
    $sql = 'SELECT * FROM show_history';
    $result = $db->arrayQuery($sql, SQLITE_ASSOC);
    foreach ($result as $show) {
        $file = $show['folder_path']."/".$show['file_name'];
        if (!file_exists($file)) {
            $db->queryexec("DELETE FROM show_history WHERE Id = ".$show['Id']);
        }
    }
}

echo "<p><a href='?clean=1' alt='Clean up' title='Clean up'><img height='24px' src='./images/trash.png'></a></p>";
$sql = 'SELECT * FROM show_history';
$result = $db->arrayQuery($sql, SQLITE_ASSOC);
$i = 0;
echo "<table><thead><th style='border:2px solid blue;'>Path</th><th style='border:2px solid blue;'>File</th><th style='border:2px solid blue;'>Watched</th><th style='border:2px solid blue;'>X</th></thead>";
foreach ($result as $show) {
    $folder_name = substr($show['folder_path'], strrpos($show['folder_path'], '/') + 1);
    $bkgd_color = $i % 2 == 0 ? "background-color:lightgray;" : "";
    echo "<tr style='".$bkgd_color."'>".
              "<td style='border:1px solid blue;'>".$show['folder_path']."</td>".
              "<td style='border:1px solid blue;'>".$show['file_name']."</td>".
              "<td style='border:1px solid blue;'>".$show['date_viewed']."</td>".
              "<td style='border:1px solid blue;'><a href='?delete=".$show['Id']."'><img height='24px' src='./images/remove.jpeg'></td>".
         "</tr>";
    $i+=1;
}
echo "</table>";

unset($db);
?>