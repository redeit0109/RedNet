<?php
$msgFile = 'msg.txt';
$uploadDir = 'downloads/';

$targetTime = '07:00:00';
$currentTime = date('H:i:s');

date_default_timezone_set('Europe/London');

if ($currentTime === $targetTime) {
    if (file_exists($msgFile)) {
        unlink($msgFile);
    }

    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $nickname = trim($_POST['nickname']) ?: 'Anonymous';
    $message = trim($_POST['message']);
    $fileText = '';
    $datetime = date('Y-m-d H:i:s');

    $message = htmlspecialchars($message);

    if (!empty($_FILES['file']['name'])) {
        $filename = basename($_FILES['file']['name']);
        $targetPath = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $fileText = " <a href=\"$targetPath\" class=\"attachment-link\" data-filename=\"$filename\" target=\"_blank\">[$filename]</a>";
        }
    }

    $fullMessage = "<b>[$datetime] $nickname</b>: $message$fileText\n";
    file_put_contents($msgFile, $fullMessage, FILE_APPEND);

    exit;
}

if (isset($_GET['fetch'])) {
    if (file_exists($msgFile)) {
        echo nl2br(file_get_contents($msgFile));
    } else {
        echo "<i>No messages.</i>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta property="og:title" content="RedNet">
    <meta property="og:description" content="Chat without any filters & moderation">
    <meta property="og:image" content="https://7f2173096eaf16.lhr.life/src/preview.png">
    <meta charset="UTF-8">
    <p>Chat clearing in 7:00 London</p>
    <link rel="icon" type="image/x-icon" href="./src/favicon.ico">
    <title>RedNet</title>
    <style>
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

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: sans-serif;
            padding: 20px;
            margin: 0;
            transition: background 0.3s, color 0.3s;
        }

        #chat {
            background-color: var(--chat-bg);
            border: 1px solid var(--border-color);
            padding: 1em;
            word-break: break-all;
            overflow-y: auto;
            margin-bottom: 20px;
            max-height: 400px;
            max-width: 700px;
            white-space: pre-wrap;
        }

        .msg {
            margin-bottom: 10px;
        }

        form input[type="text"], form input[type="file"] {
            margin: 5px;
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

<button class="theme-toggle" onclick="toggleTheme()">Change theme</button>
<button onclick="requestNotificationPermission()">Enable notifications</button>

<div id="chat"><i>Messages loading...</i></div>

<audio id="notifySound" src="./src/sound.mp3" preload="auto"></audio>

<form id="chatForm" enctype="multipart/form-data">
    <input type="text" name="nickname" id="nickname" placeholder="Nickname" required>
    <input type="text" name="message" placeholder="Message" required>
    <input type="file" name="file">
    <button type="submit">Send</button>
</form>

<script>
function toggleTheme() {
    document.body.classList.toggle('dark');
    const mode = document.body.classList.contains('dark') ? 'dark' : 'light';
    localStorage.setItem('theme', mode);
}

function saveNickname() {
    const nick = document.getElementById('nickname').value;
    localStorage.setItem('savedNickname', nick);
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
    setInterval(loadMessages, 100);
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
            previousContent = data;

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
    saveNickname();

    const form = e.target;
    const formData = new FormData(form);

    fetch('', {
        method: 'POST',
        body: formData
    }).then(() => {
        form.reset();
        document.getElementById('nickname').value = localStorage.getItem('savedNickname') || '';
        loadMessages();
    });
});
</script>

</body>
</html>
