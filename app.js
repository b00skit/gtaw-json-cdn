document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const fileRows = document.querySelectorAll('.file-row');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const copyExampleBtn = document.getElementById('copy-cdn-example');
    
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
        let parentRows = document.querySelectorAll('table.files-table tbody > tr:nth-child(odd)');
        let childRows = document.querySelectorAll('table.files-table tbody > tr:nth-child(even)');
        
        parentRows.forEach((row, index) => {
            const fileName = row.querySelector('.file-name').textContent.toLowerCase();
            if (fileName.includes(searchTerm)) {
                row.style.display = 'table-row';
                // If the parent row is visible, we need to check if the preview is open
                if (childRows[index] && !childRows[index].classList.contains('hidden')) {
                    childRows[index].style.display = 'table-row';
                }
            } else {
                row.style.display = 'none';
                // Hide the child row as well
                if (childRows[index]) {
                    childRows[index].style.display = 'none';
                }
            }
        });
    }
    
    async function fetchJsonFile(file) {
        try {
            const response = await fetch(`${baseUrl}/serve.php?file=${file}`);
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
    
    // Copy CDN example
    if (copyExampleBtn) {
        copyExampleBtn.addEventListener('click', () => {
            const exampleCode = copyExampleBtn.previousElementSibling.textContent;
            copyToClipboard(exampleCode);
        });
    }
    
    // Copy link functionality
    document.querySelectorAll('.copy-link').forEach(button => {
        button.addEventListener('click', () => {
            const file = button.getAttribute('data-file');
            const fileUrl = `${baseUrl}/serve.php?file=${file}`;
            copyToClipboard(fileUrl);
        });
    });
    
    // Copy code functionality
    document.querySelectorAll('.copy-code').forEach(button => {
        button.addEventListener('click', () => {
            const file = button.getAttribute('data-file');
            const fileUrl = `${baseUrl}/serve.php?file=${file}`;
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
        button.addEventListener('click', () => {
            const file = button.getAttribute('data-file');
            const fileUrl = `${baseUrl}/serve.php?file=${file}`;
            
            // Create a temporary anchor element
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = fileUrl;
            a.download = file;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            showToast(`Downloading ${file}`);
        });
    });
    
    // View data functionality
    document.querySelectorAll('.view-data').forEach(button => {
        button.addEventListener('click', async () => {
            const file = button.getAttribute('data-file');
            const fileNameWithoutExt = file.replace('.json', '');
            const previewRow = document.getElementById(`preview-row-${fileNameWithoutExt}`);
            
            // Toggle preview row visibility
            if (previewRow.classList.contains('hidden')) {
                previewRow.classList.remove('hidden');
                
                // Fetch and display the JSON data if on the data tab
                const dataTab = previewRow.querySelector('.tab-content[data-tab="data"]');
                const jsonPreview = dataTab.querySelector('.json-preview');
                
                if (dataTab.classList.contains('active')) {
                    jsonPreview.innerHTML = '<div class="loading">Loading data...</div>';
                    
                    const data = await fetchJsonFile(file);
                    if (data) {
                        jsonPreview.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                    } else {
                        jsonPreview.innerHTML = '<div class="error">Error loading JSON data</div>';
                    }
                }
                
                // Change button icon
                button.innerHTML = '<i class="fas fa-eye-slash"></i>';
                button.title = "Hide Data";
            } else {
                previewRow.classList.add('hidden');
                button.innerHTML = '<i class="fas fa-eye"></i>';
                button.title = "View Data";
            }
        });
    });
    
    // Tab functionality for previews
    document.querySelectorAll('.tab-btn').forEach(tab => {
        tab.addEventListener('click', async (e) => {
            const tabName = tab.getAttribute('data-tab');
            const file = tab.getAttribute('data-file');
            const previewRow = tab.closest('tr');
            
            // Update active tab
            previewRow.querySelectorAll('.tab-btn').forEach(t => {
                t.classList.remove('active');
            });
            tab.classList.add('active');
            
            // Show active content
            previewRow.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            const activeContent = previewRow.querySelector(`.tab-content[data-tab="${tabName}"]`);
            activeContent.classList.add('active');
            
            // If data tab is selected and hasn't been loaded yet, load the data
            if (tabName === 'data' && activeContent.querySelector('.json-preview .loading')) {
                const jsonPreview = activeContent.querySelector('.json-preview');
                
                // Fetch and display the JSON data
                const data = await fetchJsonFile(file);
                if (data) {
                    jsonPreview.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                } else {
                    jsonPreview.innerHTML = '<div class="error">Error loading JSON data</div>';
                }
            }
        });
    });
});