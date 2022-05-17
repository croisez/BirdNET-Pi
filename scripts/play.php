<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = new SQLite3('./scripts/birds.db', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
if($db == False){
  echo "Database is busy";
  header("refresh: 0;");
}

if (file_exists('./scripts/thisrun.txt')) {
  $config = parse_ini_file('./scripts/thisrun.txt');
} elseif (file_exists('firstrun.ini')) {
  $config = parse_ini_file('firstrun.ini');
}

$user = shell_exec("awk -F: '/1000/{print $1}' /etc/passwd");
$home = shell_exec("awk -F: '/1000/{print $6}' /etc/passwd");
$home = trim($home);

if(isset($_GET['excludefile'])) {
  if(!file_exists($home."/BirdNET-Pi/scripts/disk_check_exclude.txt")) {
    file_put_contents($home."/BirdNET-Pi/scripts/disk_check_exclude.txt", "##start\n##end");
  }
  if(isset($_GET['exclude_add'])) {
    $myfile = fopen($home."/BirdNET-Pi/scripts/disk_check_exclude.txt", "a") or die("Unable to open file!");
      $txt = $_GET['excludefile'];
      fwrite($myfile, $txt."\n");
      fwrite($myfile, $txt.".png\n");
      fclose($myfile);
      echo "OK";
      die();
  } else {
    $lines  = file($home."/BirdNET-Pi/scripts/disk_check_exclude.txt");
    $search = $_GET['excludefile'];

    $result = '';
    foreach($lines as $line) {
        if(stripos($line, $search) === false && stripos($line, $search.".png") === false) {
            $result .= $line;
        }
    }
    file_put_contents($home."/BirdNET-Pi/scripts/disk_check_exclude.txt", $result);
    echo "OK";
    die();
  }
}

if(isset($_GET['bydate'])){
  $statement = $db->prepare('SELECT DISTINCT(Date) FROM detections GROUP BY Date ORDER BY Date DESC');
  if($statement == False){
    echo "Database is busy";
    header("refresh: 0;");
  }
  $result = $statement->execute();
  $view = "bydate";

  #Specific Date
} elseif(isset($_GET['date'])) {
  $date = $_GET['date'];
  session_start();
  $_SESSION['date'] = $date;
  if(isset($_GET['sort']) && $_GET['sort'] == "occurrences") {
    $statement = $db->prepare("SELECT DISTINCT(Com_Name) FROM detections WHERE Date == \"$date\" GROUP BY Com_Name ORDER BY COUNT(*) DESC");
  } else {
    $statement = $db->prepare("SELECT DISTINCT(Com_Name) FROM detections WHERE Date == \"$date\" ORDER BY Com_Name");
  }
  if($statement == False){
    echo "Database is busy";
    header("refresh: 0;");
  }
  $result = $statement->execute();
  $view = "date";

  #By Species
} elseif(isset($_GET['byspecies'])) {
  if(isset($_GET['sort']) && $_GET['sort'] == "occurrences") {
  $statement = $db->prepare('SELECT DISTINCT(Com_Name) FROM detections GROUP BY Com_Name ORDER BY COUNT(*) DESC');
  } else {
    $statement = $db->prepare('SELECT DISTINCT(Com_Name) FROM detections ORDER BY Com_Name ASC');
  } 
  session_start();
  if($statement == False){
    echo "Database is busy";
    header("refresh: 0;");
  }
  $result = $statement->execute();
  $view = "byspecies";

  #Specific Species
} elseif(isset($_GET['species'])) {
  $species = $_GET['species'];
  session_start();
  $_SESSION['species'] = $species;
  $statement = $db->prepare("SELECT * FROM detections WHERE Com_Name == \"$species\" ORDER BY Com_Name");
  $statement3 = $db->prepare("SELECT Date, Time, Sci_Name, MAX(Confidence), File_Name FROM detections WHERE Com_Name == \"$species\" ORDER BY Com_Name");
  if($statement == False || $statement3 == False){
    echo "Database is busy";
    header("refresh: 0;");
  }
  $result = $statement->execute();
  $result3 = $statement3->execute();
  $view = "species";
} else {
  session_start();
  session_unset();
  $view = "choose";
}
?>

<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    </style>
  </head>

<script>
function toggleLock(filename, type, elem) {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {
    if(type == "add") {
     elem.setAttribute("src","images/lock.svg");
     elem.setAttribute("title", "This file is delete protected.");
     elem.setAttribute("onclick", elem.getAttribute("onclick").replace("add","del"));
    } else {
     elem.setAttribute("src","images/unlock.svg");
     elem.setAttribute("title", "This file is not delete protected.");
     elem.setAttribute("onclick", elem.getAttribute("onclick").replace("del","add"));
    }
  }
  if(type == "add") {
    xhttp.open("GET", "play.php?excludefile="+filename+"&exclude_add=true", true);
  } else {
    xhttp.open("GET", "play.php?excludefile="+filename+"&exclude_del=true", true);  
  }
  xhttp.send();
}
</script>

<?php
#If no specific species
if(!isset($_GET['species']) && !isset($_GET['filename'])){
?>
<div class="play">
<?php if($view == "byspecies" || $view == "date") { ?>
<div style="width: auto;
   text-align: center">
   <form action="" method="GET">
      <input type="hidden" name="view" value="Recordings">
      <input type="hidden" name="<?php echo $view; ?>" value="<?php echo $_GET['date']; ?>">
      <button <?php if(!isset($_GET['sort']) || $_GET['sort'] == "alphabetical"){ echo "style='background:#9fe29b !important;'"; }?> class="sortbutton" type="submit" name="sort" value="alphabetical">
         <img src="images/sort_abc.svg" alt="Sort by alphabetical">
      </button>
      <button <?php if(isset($_GET['sort']) && $_GET['sort'] == "occurrences"){ echo "style='background:#9fe29b !important;'"; }?> class="sortbutton" type="submit" name="sort" value="occurrences">
         <img src="images/sort_occ.svg" alt="Sort by occurrences">
      </button>
   </form>
</div>
<?php } ?>

<table>
  <tr>
    <form action="" method="GET">
    <input type="hidden" name="view" value="Recordings">
<?php
  #By Date
  if($view == "bydate") {
    while($results=$result->fetchArray(SQLITE3_ASSOC)){
      $date = $results['Date'];
      if(realpath($home."/BirdSongs/Extracted/By_Date/".$date) !== false){
      echo "<td>
        <button action=\"submit\" name=\"date\" value=\"$date\">$date</button></td></tr>";}}

  #By Species
  } elseif($view == "byspecies") {
    while($results=$result->fetchArray(SQLITE3_ASSOC)){
      $name = $results['Com_Name'];
      
      echo "<td>
        <button action=\"submit\" name=\"species\" value=\"$name\">$name</button></td></tr>";}

  #Specific Date
  } elseif($view == "date") {
    while($results=$result->fetchArray(SQLITE3_ASSOC)){
      $name = $results['Com_Name'];
      if(realpath($home."/BirdSongs/Extracted/By_Date/".$date."/".str_replace(" ", "_",$name)) !== false){
         echo "<td>
            <button action=\"submit\" name=\"species\" value=\"$name\">$name</button></td></tr>";
      }
    }

  #Choose
  } else {
    echo "<td>
      <button action=\"submit\" name=\"byspecies\" value=\"byspecies\">By Species</button></td></tr>
      <tr><td><button action=\"submit\" name=\"bydate\" value=\"bydate\">By Date</button></td>";
  } 

  echo "</form>
  </tr>
  </table>";
}

#Specific Species
if(isset($_GET['species'])){ ?>
<div style="width: auto;
   text-align: center">
   <form action="" method="GET">
      <input type="hidden" name="view" value="Recordings">
      <input type="hidden" name="species" value="<?php echo $_GET['species']; ?>">
      <button <?php if(!isset($_GET['sort']) || $_GET['sort'] == "date"){ echo "style='background:#9fe29b !important;'"; }?> class="sortbutton" type="submit" name="sort" value="date">
         <img width=35px src="images/sort_date.svg" alt="Sort by date">
      </button>
      <button <?php if(isset($_GET['sort']) && $_GET['sort'] == "confidence"){ echo "style='background:#9fe29b !important;'"; }?> class="sortbutton" type="submit" name="sort" value="confidence">
         <img src="images/sort_occ.svg" alt="Sort by confidence">
      </button>
   </form>
</div>
<?php
  // add disk_check_exclude.txt lines into an array for grepping
  $fp = @fopen($home."/BirdNET-Pi/scripts/disk_check_exclude.txt", 'r'); 
  if ($fp) {
     $disk_check_exclude_arr = explode("\n", fread($fp, filesize($home."/BirdNET-Pi/scripts/disk_check_exclude.txt")));
  }
  
  $name = $_GET['species'];
  if(isset($_SESSION['date'])) {
    $date = $_SESSION['date'];
    if(isset($_GET['sort']) && $_GET['sort'] == "confidence") {
        $statement2 = $db->prepare("SELECT * FROM detections where Com_Name == \"$name\" AND Date == \"$date\" ORDER BY Confidence DESC");
    } else {
        $statement2 = $db->prepare("SELECT * FROM detections where Com_Name == \"$name\" AND Date == \"$date\" ORDER BY Time DESC");
    }
  } else {
      if(isset($_GET['sort']) && $_GET['sort'] == "confidence") {
        $statement2 = $db->prepare("SELECT * FROM detections where Com_Name == \"$name\" ORDER BY Confidence DESC");
    } else {
       $statement2 = $db->prepare("SELECT * FROM detections where Com_Name == \"$name\" ORDER BY Date DESC, Time DESC");
    }
  }
  if($statement2 == False){
    echo "Database is busy";
    header("refresh: 0;");
  }
  $result2 = $statement2->execute();
  echo "<table>
    <tr>
    <th>$name</th>
    </tr>";
    $iter=0;
    while($results=$result2->fetchArray(SQLITE3_ASSOC))
    {
      $comname = preg_replace('/ /', '_', $results['Com_Name']);
      $comname = preg_replace('/\'/', '', $comname);
      $date = $results['Date'];
      $filename = "/By_Date/".$date."/".$comname."/".$results['File_Name'];
      $sciname = preg_replace('/ /', '_', $results['Sci_Name']);
      $sci_name = $results['Sci_Name'];
      $time = $results['Time'];
      $confidence = $results['Confidence'];
      $filename_formatted = $date."/".$comname."/".$results['File_Name'];

      // file was deleted by disk check, no need to show the detection in recordings
      if(!file_exists($home."/BirdSongs/Extracted/".$filename)) {
        continue;
      }
      $iter++;

      if($config["FULL_DISK"] == "purge") {
        if(!in_array($filename_formatted, $disk_check_exclude_arr)) {
          $imageicon = "images/unlock.svg";
          $title = "This file is not delete protected.";
          $type = "add";
        } else {
          $imageicon = "images/lock.svg";
          $title = "This file is delete protected.";
          $type = "del";
        }

        echo "<tr>
          <td class='relative'>$date $time<br>$confidence<br><img style='cursor:pointer' onclick='toggleLock(\"".$filename_formatted."\",\"".$type."\", this)' class=\"copyimage\" width=25 title=\"".$title."\" src=\"".$imageicon."\">
          <a href=\"$filename\"><img loading=\"lazy\" src=\"$filename.png\"></a>
          </td>
          </tr>";
      } else {
        echo "<tr>
          <td class='relative'>$date $time<br>$confidence<br>
          <a href=\"$filename\"><img loading=\"lazy\" src=\"$filename.png\"></a>
          </td>
          </tr>";
      }

    }if($iter == 0){ echo "<tr><td><b>No recordings were found.</b><br><br><span style='font-size:small'>They may have been deleted to make space for new recordings. You can modify this setting for the future in Tools -> Settings -> Advanced Settings -> Full Disk Behavior.</small></td></tr>";}echo "</table>";}

if(isset($_GET['filename'])){
  $name = $_GET['filename'];
  $statement2 = $db->prepare("SELECT * FROM detections where File_name == \"$name\" ORDER BY Date DESC, Time DESC");
  if($statement2 == False){
    echo "Database is busy";
    header("refresh: 0;");
  }
  $result2 = $statement2->execute();
  echo "<table>
    <tr>
    <th>$name</th>
    </tr>";
    while($results=$result2->fetchArray(SQLITE3_ASSOC))
    {
      $comname = preg_replace('/ /', '_', $results['Com_Name']);
      $comname = preg_replace('/\'/', '', $comname);
      $date = $results['Date'];
      $filename = "/By_Date/".$date."/".$comname."/".$results['File_Name'];
      $sciname = preg_replace('/ /', '_', $results['Sci_Name']);
      $sci_name = $results['Sci_Name'];
      $time = $results['Time'];
      $confidence = $results['Confidence'];
      $filename_formatted = $date."/".$comname."/".$results['File_Name'];

      if($config["FULL_DISK"] == "purge") {
        if(!in_array($filename_formatted, $disk_check_exclude_arr)) {
          $imageicon = "images/unlock.svg";
          $title = "This file is not delete protected.";
          $type = "add";
        } else {
          $imageicon = "images/lock.svg";
          $title = "This file is delete protected.";
          $type = "del";
        }

       echo "<tr>
          <td class=\"relative\"><img style='cursor:pointer' onclick='toggleLock(\"".$filename_formatted."\",\"".$type."\", this)' class=\"copyimage\" width=25 title=\"".$title."\" src=\"".$imageicon."\">$date $time<br>$confidence<br>
          <video onplay='setLiveStreamVolume(0)' onended='setLiveStreamVolume(1)' onpause='setLiveStreamVolume(1)' controls poster=\"$filename.png\" preload=\"none\" title=\"$filename\"><source src=\"$filename\"></video></td>
          </tr>";
      } else {
        echo "<tr>
          <td class=\"relative\">$date $time<br>$confidence<br>
          <video onplay='setLiveStreamVolume(0)' onended='setLiveStreamVolume(1)' onpause='setLiveStreamVolume(1)' controls poster=\"$filename.png\" preload=\"none\" title=\"$filename\"><source src=\"$filename\"></video></td>
          </tr>";
      }

    }echo "</table>";}?>
</div>
</html>
