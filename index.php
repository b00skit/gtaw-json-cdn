<?php
// Set base URL - Change this to your actual domain when deploying
$base_url = "https://sys.booskit.dev/cdn";

// Get all JSON files from the /json/ directory
$json_files = [];
$dir = './json/';
$total_size = 0;

if (is_dir($dir)) {
    if ($handle = opendir($dir)) {
        while (($file = readdir($handle)) !== false) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $file_path = $dir . $file;
                $file_size = filesize($file_path);
                $total_size += $file_size;
                
                // Get the last modified time
                $last_modified = filemtime($file_path);
                
                // Try to decode the JSON to count items
                $contents = file_get_contents($file_path);
                $json_data = json_decode($contents, true);
                $item_count = is_array($json_data) ? count($json_data) : 'N/A';
                
                $json_files[] = [
                    'name' => $file,
                    'size' => $file_size,
                    'size_formatted' => formatBytes($file_size),
                    'last_modified' => date('Y-m-d H:i:s', $last_modified),
                    'item_count' => $item_count
                ];
            }
        }
        closedir($handle);
    }
}

// Sort files alphabetically
usort($json_files, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Format bytes to human-readable format
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
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
            <p>copy & paste it's that easy</p>
            <div class="cdn-stats">
                <div class="stat-item">
                    <i class="fas fa-file"></i>
                    <span><?php echo count($json_files); ?> Files</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-database"></i>
                    <span><?php echo formatBytes($total_size); ?> Total</span>
                </div>
            </div>
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
                <table class="files-table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Last Modified</th>
                            <th>Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($json_files as $file): ?>
                            <tr class="file-row" data-filename="<?php echo htmlspecialchars($file['name']); ?>">
                                <td class="file-name">
                                    <a href="<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file['name']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($file['name']); ?>
                                    </a>
                                </td>
                                <td class="file-size"><?php echo htmlspecialchars($file['size_formatted']); ?></td>
                                <td class="file-date"><?php echo htmlspecialchars($file['last_modified']); ?></td>
                                <td class="file-items"><?php echo htmlspecialchars($file['item_count']); ?></td>
                                <td class="file-actions">
                                    <button class="btn-icon copy-link" data-file="<?php echo htmlspecialchars($file['name']); ?>" title="Copy URL">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <button class="btn-icon copy-code" data-file="<?php echo htmlspecialchars($file['name']); ?>" title="Copy Code">
                                        <i class="fas fa-code"></i>
                                    </button>
                                    <button class="btn-icon download-file" data-file="<?php echo htmlspecialchars($file['name']); ?>" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn-icon view-data" data-file="<?php echo htmlspecialchars($file['name']); ?>" title="View Data">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="file-preview-row hidden" id="preview-row-<?php echo htmlspecialchars(pathinfo($file['name'], PATHINFO_FILENAME)); ?>">
                                <td colspan="5">
                                    <div class="file-preview">
                                        <div class="preview-header">
                                            <h3>Preview: <?php echo htmlspecialchars($file['name']); ?></h3>
                                            <div class="preview-tabs">
                                                <button class="tab-btn active" data-tab="data" data-file="<?php echo htmlspecialchars($file['name']); ?>">Data</button>
                                                <button class="tab-btn" data-tab="fetch" data-file="<?php echo htmlspecialchars($file['name']); ?>">Fetch</button>
                                                <button class="tab-btn" data-tab="xhr" data-file="<?php echo htmlspecialchars($file['name']); ?>">XHR</button>
                                                <button class="tab-btn" data-tab="jquery" data-file="<?php echo htmlspecialchars($file['name']); ?>">jQuery</button>
                                                <button class="tab-btn" data-tab="formio" data-file="<?php echo htmlspecialchars($file['name']); ?>">FormIO</button>
                                            </div>
                                        </div>
                                        <div class="preview-content">
                                            <div class="tab-content active" data-tab="data">
                                                <div class="json-preview">
                                                    <div class="loading">Loading data...</div>
                                                </div>
                                            </div>
                                            <div class="tab-content" data-tab="fetch">
                                                <pre><code>// Using Fetch API
fetch('<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file['name']); ?>')
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
xhr.open("GET", "<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file['name']); ?>", true);
xhr.send();</code></pre>
                                            </div>
                                            <div class="tab-content" data-tab="jquery">
                                                <pre><code>// Using jQuery
$.getJSON("<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file['name']); ?>", function(data) {
  console.log(data);
  // Process your data here
});</code></pre>
                                            </div>
                                            <div class="tab-content" data-tab="formio">
                                                <pre><code>// FormIO Component with CDN
static schema() {
  return SelectComponent.schema({
    type: 'customDropdown',
    label: 'Data Selection',
    key: 'dataSelection',
    placeholder: 'Select an item',
    dataSrc: 'url',
    data: {
      url: '<?php echo $base_url; ?>/json/<?php echo htmlspecialchars($file['name']); ?>',
      headers: [
        {
          key: 'Content-Type',
          value: 'application/json'
        }
      ]
    },
    valueProperty: '', // Adjust based on your data structure
    template: '{{ item }}', // Adjust based on your data structure
    refreshOn: 'mounted'
  });
}</code></pre>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="cdn-instructions">
            <h2>How to Use This JSON CDN</h2>
            <p>This CDN allows cross-origin requests from any website. Simply use the direct URL to any JSON file:</p>
            <div class="code-block">
                <code><?php echo $base_url; ?>/json/filename.json</code>
                <button class="copy-button" id="copy-cdn-example">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            
            <h3>Features:</h3>
            <ul>
                <li><i class="fas fa-check"></i> <strong>CORS Enabled:</strong> Access from any domain without restrictions</li>
                <li><i class="fas fa-check"></i> <strong>Proper Content Type:</strong> All responses have application/json content type</li>
                <li><i class="fas fa-check"></i> <strong>Caching:</strong> Files are cached for 24 hours for better performance</li>
                <li><i class="fas fa-check"></i> <strong>Simple Implementation:</strong> Use with any JavaScript framework or library</li>
            </ul>
        </div>
    </div>

    <div id="toast" class="toast hidden">
        <span id="toast-message"></span>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> JSON CDN Directory. All rights reserved.</p>
    </footer>

    <script>
        // Pass base URL to JavaScript
        const baseUrl = "<?php echo $base_url; ?>";
    </script>
    <script src="app.js"></script>
</body>
</html>