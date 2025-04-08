document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const fileRows = document.querySelectorAll('.file-row');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    
    // Functions
    function showToast(message, duration = 3000) {
        toastMessage.textContent = message;
        toast.classList.remove('hidden');
        
        setTimeout(() => {
            toast.classList.add('hidden');
        }, duration);
    }
    
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!');
        }).catch(err => {
            console.error('Could not copy text: ', err);
            showToast('Failed to copy to clipboard!');
        });
    }
    
    function filterFiles(query) {
        const searchTerm = query.toLowerCase();
        
        fileRows.forEach(row => {
            const fileName = row.getAttribute('data-filename').toLowerCase();
            if (fileName.includes(searchTerm)) {
                row.style.display = 'block';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    async function fetchJsonFile(file) {
        try {
            const response = await fetch(`${baseUrl}/json/${file}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error fetching JSON:', error);
            return null;
        }
    }
    
    // Event Listeners
    
    // Search functionality
    searchButton.addEventListener('click', () => {
        filterFiles(searchInput.value);
    });
    
    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            filterFiles(searchInput.value);
        }
    });
    
    // Copy link functionality
    document.querySelectorAll('.copy-link').forEach(button => {
        button.addEventListener('click', () => {
            const file = button.getAttribute('data-file');
            const fileUrl = `${baseUrl}/json/${file}`;
            copyToClipboard(fileUrl);
        });
    });
    
    // Copy JS code functionality
    document.querySelectorAll('.copy-code').forEach(button => {
        button.addEventListener('click', () => {
            const file = button.getAttribute('data-file');
            const fileUrl = `${baseUrl}/json/${file}`;
            const fetchCode = `fetch('${fileUrl}')
  .then(response => response.json())
  .then(data => {
    console.log(data);
    // Process your data here
  })
  .catch(error => console.error('Error:', error));`;
            
            copyToClipboard(fetchCode);
        });
    });
    
    // Download file functionality
    document.querySelectorAll('.download-file').forEach(button => {
        button.addEventListener('click', async () => {
            const file = button.getAttribute('data-file');
            const fileUrl = `${baseUrl}/json/${file}`;
            
            try {
                const response = await fetch(fileUrl);
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = file;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                showToast(`Downloading ${file}`);
            } catch (error) {
                console.error('Download error:', error);
                showToast('Failed to download file!');
            }
        });
    });
    
    // View data functionality
    document.querySelectorAll('.view-data').forEach(button => {
        button.addEventListener('click', async () => {
            const file = button.getAttribute('data-file');
            const fileNameWithoutExt = file.replace('.json', '');
            const previewElement = document.getElementById(`preview-${fileNameWithoutExt}`);
            const previewContent = previewElement.querySelector('.preview-content');
            
            // Toggle preview
            if (previewElement.classList.contains('hidden')) {
                previewElement.classList.remove('hidden');
                
                // Fetch and display the JSON data
                const data = await fetchJsonFile(file);
                if (data) {
                    previewContent.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                } else {
                    previewContent.innerHTML = '<div class="error">Error loading JSON data</div>';
                }
                
                // Change button text
                button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Data';
            } else {
                previewElement.classList.add('hidden');
                button.innerHTML = '<i class="fas fa-eye"></i> View Data';
            }
        });
    });
    
    // Tab functionality for examples
    document.querySelectorAll('.tab-btn').forEach(tab => {
        tab.addEventListener('click', (e) => {
            const tabName = tab.getAttribute('data-tab');
            const parentRow = tab.closest('.file-row');
            
            // Update active tab
            parentRow.querySelectorAll('.tab-btn').forEach(t => {
                t.classList.remove('active');
            });
            tab.classList.add('active');
            
            // Show active content
            parentRow.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            parentRow.querySelector(`.tab-content[data-tab="${tabName}"]`).classList.add('active');
        });
    });
});