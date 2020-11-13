<?php
/*
*============================================================
*  File:		index.php
*	 Folder:	root (htdocs)
*	 By:			Enrico Webers
*	 Date:		20190325
*  Updated: Completely revamped with prepared statements,
*           and restructure for secure log-in process.
*============================================================
*/
session_start();
$searchScriptName = 'index.php?content=resources&subContentCode=searchResults';

$errorMsg = '';
if (isset($_GET['error'])) {
  if ( (string)$_GET['error'] === 'dbconnection' ) {
    $errorMsg ='A database connection could not get established.';
  } elseif ( (string)$_GET['error'] === 'emptyfields' ) {
    $errorMsg ='You\'ve left the user and/or password field empty.';
  } elseif ( (string)$_GET['error'] === 'sqlerror' ) {
    $errorMsg ='Error executing log in query.';
  } elseif ( (string)$_GET['error'] === 'passwordincorrect' ) {
    $errorMsg ='You\'ve entered an incorrect password.';
  } elseif ( (string)$_GET['error'] === 'nouser' ) {
    $errorMsg ='User Name Unknown.';
  } elseif ( (string)$_GET['error'] === 'pwderror' ) {
    $errorMsg ='Error checking entered password.';
  } else {
    $errorMsg ='Undefined error logging in.';
  }
}

include_once "../htconfig/dbConfig.php";
include_once "includes/20_dbConnect.php";
include_once "includes/20_header.php";

global $conn;
global $db;
include_once "../htconfig/dbConfig.php";
include_once "includes/20_dbConnect.php";
$pdo = new PDO(sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4",
$db['hostname'],
$db['database']),
$db['username'], $db['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ( !isset($_SESSION['userId']) || isset($_GET['error'])) {
  echo "<style>
    body {
      background-image: url('images/bp21b.png');
      background-repeat: no-repeat;
      background-attachment: fixed;
      background-size: auto 100%;
    }
    </style>";
  include_once "includes/20_loginForm.php";

} else {

  include_once "includes/20_functions.php";
  include_once "includes/20_initialise.php";
  include "includes/20_initialValues.php";
  $menuFile = '';
  $contentFile = '';
  $accessLevel = $_SESSION["userAccessLevel"];
  $BPOnly = $_SESSION["userBPOnly"];
  $userID = $_SESSION["userId"];
  $userName = $_SESSION["userName"];

// echo $userName;

  if (isset($_GET['content'])) {
    $contentCode = $_GET['content'];
  } else {
    $contentCode = "Welcome";
  }
  if (isset($_GET['subContentCode'])) {
    $subContentCode = (string)$_GET['subContentCode'];
  } else {
    $subContentCode = "1";
  }
  if ($subContentCode === "1" ) {
    $contentCode = mysqli_real_escape_string($conn,$contentCode);
    $sql = "SELECT contentFile, menuMinAccess
    FROM t_menus
    WHERE menuContentCode = :contentCode ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':contentCode',$contentCode);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $contentFile = $row['contentFile'];
      $minAccessLevel = $row['menuMinAccess'];
    }
  } else {
    $subContentCode = mysqli_real_escape_string($conn,$subContentCode);
    $sql = "SELECT contentFile, menuMinAccess
            FROM t_menus
            WHERE menuContentCode = ? ";
    $stmt = mysqli_stmt_init($conn);
    mysqli_stmt_prepare($stmt, $sql);
    mysqli_stmt_bind_param($stmt,"s",$subContentCode);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ( $row = mysqli_fetch_assoc($result) ) {
      $contentFile = $row['contentFile'];
      $minAccessLevel = $row['menuMinAccess'];
    }
  }

  $i1 = 0;
  $sql = "SELECT menuID, menuTitle, menuContentCode, menuMinAccess
  FROM t_menus
  WHERE menuLevel = 1 ";
  if ( $BPOnly === "Yes" ) { $sql .= "AND menuContentCode = 'businessPlanning' "; }
  $sql .= " AND menuShow=True
  AND menuMinAccess <= :accessLevel
  ORDER BY menuOrder;";
  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':accessLevel',$accessLevel);
  $stmt->execute();
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $menuID1[$i1] = $row['menuID'];
    $menuContentCode1[$i1] = $row['menuContentCode'];
    $menuTitle1[$i1] = $row['menuTitle'];
    $i1++;
  }

  for ( $i = 0 ; $i < $i1 ; $i++ ) {
    $i2 = 0;
    $sql = "SELECT menuID, menuTitle, menuContentCode, menuMinAccess
    FROM t_menus
    WHERE menuLevel = 2
    AND menuShow=True
    AND menuMinAccess <= :accessLevel
    AND menuParent = :menuParent
    ORDER BY menuOrder;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':accessLevel',$accessLevel);
    $stmt->bindParam(':menuParent',$menuID1[$i]);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $menuID2[$i][$i2] = $row['menuID'];
      $menuContentCode2[$i][$i2] = $row['menuContentCode'];
      $menuMinAccess[$i][$i2] = $row['menuMinAccess'];
      $menuTitle2[$i][$i2] = $row['menuTitle'];
      $i2++;
    }
    $c2[$i] = $i2;
  }

  /*
      */
?>
    <div class="clearfix"></div>
    <nav class="navbar-remark navbar-expand-xl bg-light-remark">

    <a class="pl-3 pr-2 py-1" href="index.php">
      <img src="/images/ReMarkWhiteLogo.png" border="0" alt="ReMark_MI_Logo" height="50px">
    </a>

    <button class="navbar-toggler mr-2" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mr-auto">
  <?php if  ( $BPOnly === "Yes" ) { $starti = 0; } else  { $starti = 1; }
      for ( $i = $starti ; $i < $i1 ; $i++ ) { ?>
        <li class="nav-item dropdown ml-2">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <?php echo $menuTitle1[$i]; ?>
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
    <?php for ( $j = 0 ; $j < $c2[$i] ; $j++ ) {
          if ( $menuContentCode2[$i][$j] == 'DIVIDER' ) {
            echo '<div class="dropdown-divider"></div>';
          } else {
            echo '<a class="dropdown-item" href="index.php?content='.$menuContentCode1[$i].'&subContentCode='.$menuContentCode2[$i][$j].'">';
            if ( $menuMinAccess[$i][$j] > 50 ) { echo'<img src="/images/asterisk.png" alt="Restricted Access" style="width:14px;height:14px;border:0;">';}
            echo '&nbsp;'.$menuTitle2[$i][$j].'</a>';
          }
        } ?>
          </div>
        </li>
  <?php }
    if ( $userName == 'BP21_China' ) {
      echo '<a class="bg-white p-1 m-1" href="index.php?content='.$menuContentCode1[0].'&subContentCode='.$menuContentCode2[0][0].'">'.$menuTitle2[0][0].'</a>';
      echo '<a class="bg-white p-1 m-1" href="index.php?content='.$menuContentCode1[0].'&subContentCode='.$menuContentCode2[0][1].'">'.$menuTitle2[0][1].'</a>';
      echo '<a class="bg-white p-1 m-1" href="index.php?content='.$menuContentCode1[0].'&subContentCode='.$menuContentCode2[0][2].'">'.$menuTitle2[0][2].'</a>';
      echo '<a class="bg-white p-1 m-1" href="index.php?content='.$menuContentCode1[0].'&subContentCode='.$menuContentCode2[0][3].'">'.$menuTitle2[0][3].'</a>';
      echo '<a class="bg-white p-1 m-1" href="index.php?content='.$menuContentCode1[0].'&subContentCode='.$menuContentCode2[0][4].'">'.$menuTitle2[0][4].'</a>';
      echo '<a class="bg-white p-1 m-1" href="index.php?content='.$menuContentCode1[0].'&subContentCode='.$menuContentCode2[0][5].'">'.$menuTitle2[0][5].'</a>';
      echo '<a class="bg-white p-1 m-1" href="index.php?content='.$menuContentCode1[0].'&subContentCode='.$menuContentCode2[0][6].'">'.$menuTitle2[0][6].'</a>';
    }



  ?>
      </ul>
    </div>

    <form class="form-inline m-2" action="index.php?content=resources&subContentCode=searchResults">
        <input class="form-control mr-sm-2" type="search" name="searchString" placeholder="Search" aria-label="Search">
        <button class="btn btn-outline-white mr-4 my-2 my-sm-0" type="submit">Search</button>
    </form>
  </nav>
<?php



// MAIN CONTENT
  $searchString = inputValuePG('searchString','');
  echo '<div class="container-fluid text-dark pt-3 pl-5 pr-5 mb-2">';
  if ( isset($contentCode) and $contentCode === "Welcome" ) {
    if ( $searchString !== '' ) {
      include_once('includes/Snippets/20_searchResults.php');
    } else {
      include($contentFile);
    }
  } else {
    echo '<div>';
    if (file_exists($contentFile)) {
      $nowTimeStamp = date("Y-m-d H:i:s");
      if (isset($_SESSION["userName"])) {
        $sql = "INSERT into t_log (LoginTime, userName, SectionName)
                VALUES (?,?,?)";
        $stmt = mysqli_stmt_init($conn);
        mysqli_stmt_prepare($stmt, $sql);
        mysqli_stmt_bind_param($stmt,"sss",$nowTimeStamp,$_SESSION["userName"],$contentFile);
        mysqli_stmt_execute($stmt);
      }
      echo '<div>';
        include($contentFile);
      echo '</div>';
    }
    echo '</div>';
  }
  echo '</div>';
  echo '<div style="clear:both;"></div>';
?>


<?php if ( $BPOnly === "Yes" ) { ?>
  <footer class="footer navbar-light bg-info-light mt-3">
    <a class="ml-4 navbar-brand text-muted"><h6><small>&copy;<?php echo $_SESSION['currentOPYear']; ?> ReMark International B.V.<br><strong>RESTRICTED</strong><br>All details in this Business Planning BV Projection Tool are strictly confidential.</small></h6></a>
    <form class="float-right mr-5 mt-1" action="includes/20_logoutDestroy.php" method="post">
      <button class="btn btn-outline-white bg-light-remark ml-4 mt-3" type="submit" name="logout-submit">Log Out</button>
    </form>
  </footer>
<?php } else { ?>
  <footer class="footer navbar-light bg-info-light mt-3">
    <a class="ml-4 navbar-brand text-muted"><h6><small>&copy;<?php echo $_SESSION['currentOPYear']; ?> ReMark International B.V.  All details in this Dashboard strictly for Internal Use.<br>All monetary amounts in corporate currency (EUR), unless otherwise indicated. This Dashboard contains data from frozen OPs only.<br>There is no live OP information in this Dashboard.</small></h6></a>
<?php if ( $_SESSION["userAccessLevel"] >= 90 ) {
        echo '<div class="mt-3 mr-5 float-right">
          <a href="index.php?content=adminTools&subContentCode=uploadOPData">
            <img src="/images/upload.png" alt="ReMark_MI_Logo" border="0" height="35">
          </a>
        </div>';
      } ?>
    <form class="float-right mr-5 mt-1" action="includes/20_logoutDestroy.php" method="post">
      <button class="btn btn-outline-white bg-light-remark ml-4 mt-3" type="submit" name="logout-submit">Log Out</button>
    </form>
  </footer>
<?php }
  }
?>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
</body>

</html>
