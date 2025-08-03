<?php
session_start();

$msgFile = 'msg.txt';
$uploadDir = 'downloads/';
$userFile = 'src/users.json';
$loggedInUser = $_SESSION['username'] ?? null;

date_default_timezone_set('Europe/London');

$currentTime = date('H:i:s');

if (isset($_POST['delete_account']) && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    if (file_exists($userFile)) {
        $users = json_decode(file_get_contents($userFile), true);

        if (isset($users[$username])) {
            unset($users[$username]);
            file_put_contents($userFile, json_encode($users));
        }
    }

    session_unset();
    session_destroy();
    echo 'Account deleted';
    exit;
}
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!file_exists($userFile)) file_put_contents($userFile, '{}');
    $users = json_decode(file_get_contents($userFile), true);

    if (isset($users[$username])) {
        echo 'User already exists';
        exit;
    }

    $users[$username] = $password;
    file_put_contents($userFile, json_encode($users));
    $_SESSION['username'] = $username;
    echo 'Registered';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!file_exists($userFile)) exit('No users');
    $users = json_decode(file_get_contents($userFile), true);

    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        $_SESSION['username'] = $username;
        echo 'Logged in';
    } else {
        echo 'Invalid credentials';
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $nickname = $_SESSION['username'] ?? 'Anonymous';
    $message = htmlspecialchars(trim($_POST['message']));
    $replyTo = isset($_POST['reply_to']) ? trim($_POST['reply_to']) : '';
    $fileText = '';
    $datetime = date('Y-m-d H:i:s');

    if (!empty($_FILES['file']['name'])) {
        $filename = basename($_FILES['file']['name']);
        $targetPath = $uploadDir . $filename;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $fileText = " <a href=\"$targetPath\" class=\"attachment-link\" data-filename=\"$filename\" target=\"_blank\">[$filename]</a>";
        }
    }

    $replyText = '';
    if (!empty($replyTo)) {
        $replyText = "<div class=\"reply\">Reply to <b>$replyTo</b></div>";
    }

    $fullMessage = "<div class=\"msg\">$replyText<b>[$datetime] $nickname</b>: $message$fileText</div>\n";
    file_put_contents($msgFile, $fullMessage, FILE_APPEND);
    exit;
}

if (isset($_GET['fetch'])) {
    if (file_exists($msgFile)) {
        $lines = file($msgFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastLines = array_slice($lines, -12);
        echo nl2br(implode("\n", $lastLines));
    } else {
        echo "<h1><i>No messages.</i></h1>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="./src/favicon.ico">
    <title>RedNet</title>
    <style>

       #authPopup {
             display: none;
             position: fixed;
             top: 20%;
             left: 50%;
             transform: translateX(-50%);
             padding: 30px;
             border: 1px solid var(--border-color);
             background: var(--chat-bg);
             color: var(--text-color);
             z-index: 1000;
             width: 320px;
             border-radius: 10px;
             box-shadow: 0 0 15px rgba(0,0,0,0.5);
       }

       #authPopup input {
             width: 90%;
             padding: 8px;
             margin: 8px 0;
             background: var(--bg-color);
             color: var(--text-color);
             border: 1px solid var(--border-color);
             border-radius: 5px;
        }

        #authPopup button {
             padding: 8px 12px;
             margin: 5px 4px;
             background-color: var(--bg-color);
             color: var(--text-color);
             border: 1px solid var(--border-color);
             cursor: pointer;
             border-radius: 5px;
        }

        #authPopup h3 {
             margin-top: 0;
             text-align: center;
        }

        :root {
            --bg-color: #ffffff;
            --text-color: #000000;
            --chat-bg: #f0f0f0;
            --border-color: #cccccc;
        }

        body.dark {
            --bg-color: #121212;
            --text-color: #eeeeee;
            --chat-bg: #1e1e1e;
            --border-color: #444444;
        }

        body.dark button {
            padding: 4px 6px;
            margin: 2.5px 2px;
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            cursor: pointer;
            border-radius: 5px;
        }

        body.dark input {
            padding: 4px 6px;
            margin: 2.5px 2px;
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            cursor: pointer;
            border-radius: 5px;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: sans-serif;
            padding: 20px;
            margin: 0;
            transition: background 0.3s, color 0.3s;
        }

        body button {
            padding: 4px 6px;
            margin: 2.5px 2px;
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            cursor: pointer;
            border-radius: 5px;
        }

        body input {
            padding: 4px 6px;
            margin: 2.5px 2px;
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            cursor: pointer;
            border-radius: 5px;
        }

        #chat {
            background-color: var(--chat-bg);
            border: 1px solid var(--border-color);
            padding: 1em;
            word-break: break-all;
            overflow-y: auto;
            margin-bottom: 20px;
            max-height: 440px;
            max-width: 1300px;
        }

        .msg {
            margin-bottom: 10px;
        }

        .reply {
           font-style: italic;
           font-size: 0.9em;
           color: var(--text-color);
           margin-bottom: 4px;
       }

        button.theme-toggle {
            margin-bottom: 15px;
            padding: 5px 10px;
            cursor: pointer;
        }

        a {
            color: var(--text-color);
        }
    </style>
</head>
<body>

<div id="authControls"></div>

<button class="theme-toggle" onclick="toggleTheme()">Change theme</button>
<button onclick="requestNotificationPermission()">Enable notifications</button>

<div id="chat"><i>Messages loading...</i></div>

<audio id="notifySound" src="./src/sound.mp3" preload="auto"></audio>

<form id="chatForm" enctype="multipart/form-data">
    <input type="hidden" name="reply_to" id="replyTo">
    <input type="text" name="message" placeholder="Message" required autocomplete="off">
    <input type="file" name="file">
    <button type="submit">Send</button>
</form>

<script>
function toggleTheme() {
    document.body.classList.toggle('dark');
    const mode = document.body.classList.contains('dark') ? 'dark' : 'light';
    localStorage.setItem('theme', mode);
}

function updateAuthControls(loggedInUser = null) {
    const container = document.getElementById('authControls');
    container.innerHTML = '';

    const userLabel = document.createElement('span');
    userLabel.style.marginRight = '10px';
    if (loggedInUser) {
        const h1 = document.createElement('h1');
        h1.textContent = loggedInUser;
        h1.style.display = 'inline';
        h1.style.marginRight = '15px';
        container.appendChild(h1);
    }
    container.appendChild(userLabel);

    if (loggedInUser) {
        const logoutBtn = document.createElement('button');
        logoutBtn.textContent = 'Logout';
        logoutBtn.onclick = () => window.location.href = '?logout=1';
        container.appendChild(logoutBtn);

        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = 'Delete Account';
        deleteBtn.style.marginLeft = '5px';
        deleteBtn.onclick = deleteAccount;
        container.appendChild(deleteBtn);
    } else {
        const loginBtn = document.createElement('button');
        loginBtn.textContent = 'Login / Register';
        loginBtn.onclick = showLogin;
        container.appendChild(loginBtn);
    }
}

function showLogin() {
    document.getElementById('authPopup').style.display = 'block';
}
function hideLogin() {
    document.getElementById('authPopup').style.display = 'none';
}

function submitAuth(action) {
    const username = document.getElementById('authUsername').value;
    const password = document.getElementById('authPassword').value;

    const body = `${action}=1&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`;

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    }).then(res => res.text()).then(text => {
        alert(text);
        if (text.includes('Logged in') || text.includes('Registered')) {
            updateAuthControls(username);
            hideLogin();
        }
    });
}

function deleteAccount() {
    if (!confirm('Are you sure you want to delete your account? This cannot be undone.')) return;

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'delete_account=1'
    })
    .then(res => res.text())
    .then(text => {
        alert(text);
        updateAuthControls(null);
        loadMessages();
    });
}

function requestNotificationPermission() {
    if (!("Notification" in window)) {
        alert("This browser does not support desktop notifications");
        return;
    }

    if (Notification.permission === "granted") {
        alert("Notifications are already enabled");
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                alert("Notifications enabled");
            } else {
                alert("Notifications denied");
            }
        });
    } else {
        alert("You have denied notifications");
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    updateAuthControls(<?php echo json_encode($loggedInUser); ?>);

    if (savedTheme) {
        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
        }
    } else {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
            document.body.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
           localStorage.setItem('theme', 'light');
        }
    }

    const savedNick = localStorage.getItem('savedNickname');
    if (savedNick) {
        document.getElementById('nickname').value = savedNick;
    }

    loadMessages();
    setInterval(loadMessages, 450);
});

let previousContent = "";

function loadMessages() {
    fetch("?fetch=1")
        .then(res => res.text())
        .then(data => {
            const chatBox = document.getElementById('chat');
            if (data !== previousContent) {
            if (previousContent !== "") {
                const sound = document.getElementById('notifySound');
                if (sound) {
                        sound.volume = 0.2;
                        sound.play();
                    }
                }

                if (Notification.permission === "granted" && document.hidden) {
                    new Notification("RedNet", {
                        body: "New message",
                        icon: "./src/favicon.ico"
                 });
            }

            document.getElementById('chat').innerHTML = data;
            enhanceAttachments();
            addReplyButtons();
            previousContent = data;

            }
        });
}

function addReplyButtons() {
    const chatBox = document.getElementById('chat');
    const messages = chatBox.querySelectorAll('.msg');

    messages.forEach(msg => {
        const userMatch = msg.innerHTML.match(/\[(.*?)\] (.*?):/);
        if (!userMatch) return;

        const username = userMatch[2];
        const replyBtn = document.createElement('button');
        replyBtn.textContent = 'Reply';
        replyBtn.style.marginLeft = '10px';
        replyBtn.style.fontSize = '0.8em';
        replyBtn.onclick = () => {
            document.getElementById('replyTo').value = username;
            document.querySelector('input[name="message"]').focus();
        };

        if (!msg.querySelector('button')) {
            msg.appendChild(replyBtn);
        }
    });
}

function enhanceAttachments() {
    const links = document.querySelectorAll('#chat .attachment-link');

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const href = link.getAttribute('href');
            const filename = link.dataset.filename.toLowerCase();

            let preview;
            if (filename.match(/\.(jpg|jpeg|png|gif|webp|bmp)$/)) {
                preview = document.createElement('img');
                preview.src = href;
                preview.style.maxWidth = '100%';
                preview.style.marginTop = '5px';
            } else if (filename.match(/\.(mp4|webm|ogg)$/)) {
                preview = document.createElement('video');
                preview.src = href;
                preview.controls = true;
                preview.style.maxWidth = '100%';
                preview.style.marginTop = '5px';
            } else if (filename.match(/\.(mp3|wav|ogg)$/)) {
                preview = document.createElement('audio');
                preview.src = href;
                preview.controls = true;
                preview.style.marginTop = '5px';
            }

            if (preview) {
                link.replaceWith(preview);
            } else {
                window.open(href, '_blank');
            }
        });
    });
}

document.getElementById('chatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    fetch('', {
        method: 'POST',
        body: formData
    }).then(() => {
        form.reset();
        document.getElementById('replyTo').value = '';
        loadMessages();
    });
});
</script>

<div id="authPopup">
    <h3>Login or Register</h3>
    <input type="text" id="authUsername" placeholder="Username"><br><br>
    <input type="password" id="authPassword" placeholder="Password"><br><br>
    <button onclick="submitAuth('login')">Login</button>
    <button onclick="submitAuth('register')">Register</button>
    <button onclick="hideLogin()">Cancel</button>
</div>

</body>
</html>
