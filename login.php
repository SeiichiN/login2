<?php
// require 'password.php';   // password_verfy()はphp 5.5.0以降の関数のため、バージョンが古くて使えない場合に使用
// セッション開始
session_start();

// エラーメッセージの初期化
$errorMessage = "";

require_once('./dbcheck.php');
require_once('./db.conf');

dbcheck($db);

$message = null;
if ($_SESSION['mes'] != null) {
  $message = $_SESSION['mes'];
  $_SESSION['mes'] = null;
}

// ログインボタンが押された場合
if (isset($_POST["login"])) {
  // 1. ユーザIDの入力チェック
  if (empty($_POST["userid"])) {  // emptyは値が空のとき
    $errorMessage = 'ユーザーIDが未入力です。';
  } else if (empty($_POST["password"])) {
    $errorMessage = 'パスワードが未入力です。';
  }

  if (!empty($_POST["userid"]) && !empty($_POST["password"])) {
    // 入力したユーザIDを格納
    $userid = $_POST["userid"];

    // 2. ユーザIDとパスワードが入力されていたら認証する
    $dsn = sprintf('mysql: host=%s; dbname=%s; charset=utf8', $db['host'], $db['dbname']);

    // 3. エラー処理
    try {
      $pdo = new PDO($dsn, $db['user'], $db['pass']);
	  // クエリ発行のたびにエラーを出力し、try..catch..で処理する。
	  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $sql = "SELECT * FROM " . $db['dbtable'] . " WHERE name = ?";
      $stmt = $pdo->prepare($sql);
	  // prepareでの？への値の渡し方は、配列でないといけない。
	  // ？が複数ある場合もあるからなあ。
      $stmt->execute(array($userid));

      $password = $_POST["password"];

      if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, $row['password'])) {
		  // セッションIDを新しく作りなおす。
          session_regenerate_id(true);

          // 入力したIDのユーザー名を取得
          $id = $row['id'];
          $sql = "SELECT * FROM " . $db['dbtable'] . " WHERE id = " . $id;  //入力したIDからユーザー名を取得
          $stmt = $pdo->query($sql);
          foreach ($stmt as $row) {
            $row['name'];  // ユーザー名
          }
          $_SESSION["NAME"] = $row['name'];
          header("Location: main.php");  // メイン画面へ遷移
          exit();  // 処理終了
        } else {
          // 認証失敗
          $errorMessage = '(認証失敗)ユーザーIDあるいはパスワードに誤りがあります。';
        }
      } else {
        // 4. 認証成功なら、セッションIDを新規に発行する
        // 該当データなし
        $errorMessage = '(該当データなし)ユーザーIDあるいはパスワードに誤りがあります。';
      }
    } catch (PDOException $e) {
      $errorMessage = 'データベースエラー';
      //$errorMessage = $sql;
      // $e->getMessage() でエラー内容を参照可能（デバック時のみ表示）
      echo $e->getMessage();
    }
  }
}
?>

<!doctype html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>ログイン</title>
  </head>
  <body>
    <div><?php if ($message) { echo $message; } ?></div>
    <h1>ログイン画面</h1>
    <form id="loginForm" name="loginForm" action="" method="POST">
      <fieldset>
        <legend>ログインフォーム</legend>
        <div>
          <font color="#ff0000">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES); ?>
          </font>
        </div>
        <label for="userid">ユーザーID</label>
        <input type="text" id="userid" name="userid" placeholder="ユーザーIDを入力" 
               value="<?php if (!empty($_POST["userid"])) {echo htmlspecialchars($_POST["userid"], ENT_QUOTES);} ?>">
        <br>
        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" value="" placeholder="パスワードを入力">
        <br>
        <input type="submit" id="login" name="login" value="ログイン">
      </fieldset>
    </form>
    <br>
    <form action="signup.php">
      <fieldset>          
        <legend>新規登録フォーム</legend>
        <input type="submit" value="新規登録">
      </fieldset>
    </form>
  </body>
</html>

修正時刻： Fri May 15 13:41:25 2020
