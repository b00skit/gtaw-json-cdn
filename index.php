<?php
// Set base URL - Change this to your actual domain when deploying
$base_url = "https://sys.booskit.dev/cdn";

// Get all JSON files from the /json/ directory
$json_files = [];
$dir = './json/';

if (is_dir($dir)) {
    if ($handle = opendir($dir)) {
        while (($file = readdir($handle)) !== false) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $json_files[] = $file;
            }
        }
        closedir($handle);
    }
}

// Sort files alphabetically
sort($json_files);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>booskit's json cdn</title>
    <link rel="stylesheet" href="app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>booskit's json cdn</h1>
            <p>copy and paste it's that easy.</p>
        </header>

        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Search JSON files...">
            <button id="search-button"><i class="fas fa-search"></i></button>
        </div>

        <div class="files-container">
            <?php if (empty($json_files)): ?>
                <div class="no-files">
                    <p>No JSON files found in the directory.</p>
                </div>
            <?php else: ?>
                <?php foreach ($json_files as $file): ?>
                    <div class="file-row" data-filename="<?php echo htmlspecialchars($file); ?>">
                        <div class="file-info">
                            <h2><?php echo htmlspecialchars($file); ?></h2>
                            <div class="file-actions">
                                <button class="btn copy-link" data-file="<?php echo htmlspecialchars($file); ?>">
                                    <i class="fas fa-link"></i> Copy Link
                                </button>
                                <button class="btn copy-code" data-file="<?php echo htmlspecialchars($file); ?>">
                                    <i class="fas fa-code"></i> Copy JS Code
                                </button>
                                <button class="btn download-file" data-file="<?php echo htmlspecialchars($file); ?>">
                                    <i class="fas fa-download"></i> Download
                                </button>
                                <button class="btn view-data" data-file="<?php echo htmlspecialchars($file); ?>">
                                    <i class="fas fa-eye"></i> View Data
                                </button>
                            </div>
                        </div>
                        <div class="file-preview hidden" id="preview-<?php echo htmlspecialchars(pathinfo($file, PATHINFO_FILENAME)); ?>">
                            <div class="preview-content">
                                <div class="loading">Loading...</div>
                            </div>
                        </div>
                        <div class="file-examples">
                            <h3>Implementation Examples</h3>
                            <div class="example-tabs">
                                <button class="tab-btn active" data-tab="fetch">Fetch API</button>
                                <button class="tab-btn" data-tab="xhr">XMLHttpRequest</button>
                                <button class="tab-btn" data-tab="jquery">jQuery</button>
                            </div>
                            <div class="example-content">
                                <div class="tab-content active" data-tab="fetch">
                                    <pre><code>// Using Fetch API
fetch('<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file); ?>')
  .then(response => response.json())
  .then(data => {
    console.log(data);
    // Process your data here
  })
  .catch(error => console.error('Error:', error));</code></pre>
                                </div>
                                <div class="tab-content" data-tab="xhr">
                                    <pre><code>// Using XMLHttpRequest
var xhr = new XMLHttpRequest();
xhr.onreadystatechange = function() {
  if (this.readyState == 4 && this.status == 200) {
    var data = JSON.parse(this.responseText);
    console.log(data);
    // Process your data here
  }
};
xhr.open("GET", "<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file); ?>", true);
xhr.send();</code></pre>
                                </div>
                                <div class="tab-content" data-tab="jquery">
                                    <pre><code>// Using jQuery
$.getJSON("<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file); ?>", function(data) {
  console.log(data);
  // Process your data here
});</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="toast" class="toast hidden">
        <span id="toast-message"></span>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> booskit.dev</p>
    </footer>

    <script>
        // Pass base URL to JavaScript
        const baseUrl = "<?php echo $base_url; ?>";
    </script>
    <script src="app.js"></script>
</body>
</html>