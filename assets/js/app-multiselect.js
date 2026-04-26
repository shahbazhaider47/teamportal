/**
 * Multi-select dropdown functionality
 * Consolidated version for both add and edit modals
 */

// Global variable to track all multi-select instances
window.multiSelectInstances = new Map();

// Initialize all multi-select dropdowns
function initMultiSelectDropdowns() {
    // Select all multi-select dropdowns
    const multiSelects = document.querySelectorAll('.multi-select-wrapper');
    
    multiSelects.forEach((wrapper, index) => {
        const selectElement = wrapper.querySelector('select');
        const wrapperId = selectElement.id || `multi-select-${index}`;
        
        // Only initialize if not already initialized
        if (!wrapper.classList.contains('multi-select-initialized')) {
            // Store instance
            window.multiSelectInstances.set(wrapperId, {
                wrapper: wrapper,
                selectElement: selectElement,
                searchTerm: '',
                isInitialized: false
            });
            
            // Initialize
            convertToMultiSelect(wrapper, selectElement);
            setupMultiSelectEvents(wrapper);
            updateMultiSelectDisplay(wrapper);
            
            // Mark as initialized
            wrapper.classList.add('multi-select-initialized');
        }
    });
}

// Convert regular select to multi-select HTML
function convertToMultiSelect(wrapper, selectElement) {
    const options = Array.from(selectElement.options);
    const dropdown = wrapper.querySelector('.multi-select-dropdown');
    
    // Clear existing dropdown content
    dropdown.innerHTML = '';
    
    // Create search input
    const searchDiv = document.createElement('div');
    searchDiv.className = 'multi-select-search';
    
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search...';
    searchInput.className = 'multi-select-search-input';
    
    searchDiv.appendChild(searchInput);
    dropdown.appendChild(searchDiv);
    
    // Create options container
    const optionsContainer = document.createElement('div');
    optionsContainer.className = 'multi-select-options';
    dropdown.appendChild(optionsContainer);
    
    // Store original options
    wrapper.dataset.originalOptions = JSON.stringify(options.map(opt => ({
        value: opt.value,
        text: opt.textContent,
        selected: opt.selected
    })));
    
    // Create checkboxes for all options (no limit, scrollable instead)
    renderOptions(wrapper, '');
    
    // Hide the original select
    selectElement.style.display = 'none';
    
    // Update display
    updateMultiSelectDisplay(wrapper);
}

// Render options with search filtering
function renderOptions(wrapper, searchTerm = '') {
    const selectElement = wrapper.querySelector('select');
    const dropdown = wrapper.querySelector('.multi-select-dropdown');
    const optionsContainer = dropdown.querySelector('.multi-select-options');
    
    // Parse original options
    const originalOptions = JSON.parse(wrapper.dataset.originalOptions || '[]');
    
    // Filter options based on search term
    let filteredOptions = originalOptions;
    if (searchTerm) {
        const term = searchTerm.toLowerCase();
        filteredOptions = originalOptions.filter(opt => 
            opt.text.toLowerCase().includes(term)
        );
    }
    
    // Clear options container
    optionsContainer.innerHTML = '';
    
    if (filteredOptions.length === 0) {
        // Show no results message
        const noResults = document.createElement('div');
        noResults.className = 'multi-select-no-results';
        noResults.textContent = 'No results found';
        optionsContainer.appendChild(noResults);
    } else {
        // Get current selected values from the original select element
        const selectedValues = Array.from(selectElement.selectedOptions).map(opt => opt.value);
        
        // Create checkbox for each filtered option
        filteredOptions.forEach(option => {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'multi-select-option';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = `${selectElement.name}_${option.value}_${Date.now()}_${Math.random()}`;
            checkbox.value = option.value;
            checkbox.checked = selectedValues.includes(option.value);
            
            const label = document.createElement('label');
            label.htmlFor = checkbox.id;
            label.textContent = option.text;
            label.title = option.text;
            label.style.cursor = 'pointer';
            label.style.marginBottom = '0';
            
            optionDiv.appendChild(checkbox);
            optionDiv.appendChild(label);
            optionsContainer.appendChild(optionDiv);
        });
    }
    
    // Store current filter state
    wrapper.dataset.currentSearch = searchTerm;
}

// Setup event listeners for multi-select
function setupMultiSelectEvents(wrapper) {
    const displayElement = wrapper.querySelector('.multi-select-display');
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    const clearBtn = wrapper.querySelector('.multi-select-clear');
    const selectElement = wrapper.querySelector('select');
    const searchInput = dropdownElement.querySelector('.multi-select-search-input');
    
    // Toggle dropdown on display click
    displayElement.addEventListener('click', function(e) {
        e.stopPropagation();
        const isShowing = dropdownElement.classList.contains('show');
        
        // Close all other dropdowns
        document.querySelectorAll('.multi-select-dropdown.show').forEach(dropdown => {
            if (dropdown !== dropdownElement) {
                dropdown.classList.remove('show');
            }
        });
        
        dropdownElement.classList.toggle('show', !isShowing);
        
        // Focus search input when dropdown opens
        if (dropdownElement.classList.contains('show')) {
            setTimeout(() => {
                searchInput.focus();
                searchInput.select();
            }, 100);
        }
    });
    
    // Handle search input
    searchInput.addEventListener('input', function(e) {
        const searchTerm = e.target.value.trim();
        renderOptions(wrapper, searchTerm);
    });
    
    // Handle checkbox changes - FIXED: Use event delegation
    dropdownElement.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox') {
            handleCheckboxChange(wrapper, e.target);
            updateSelectedValues(wrapper);
            updateMultiSelectDisplay(wrapper);
        }
    });
    
    // Handle clicks on labels - FIXED: Use event delegation
    dropdownElement.addEventListener('click', function(e) {
        // If clicking on a label, trigger the checkbox
        if (e.target.tagName === 'LABEL') {
            const checkboxId = e.target.getAttribute('for');
            if (checkboxId) {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            }
        }
    });
    
    // Clear all selections
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            clearAllSelections(wrapper);
            updateSelectedValues(wrapper);
            updateMultiSelectDisplay(wrapper);
        });
    }
    
    // Close dropdown when clicking outside - FIXED: Better check
    document.addEventListener('click', function(e) {
        // Check if click is outside the wrapper and dropdown is open
        if (!wrapper.contains(e.target) && dropdownElement.classList.contains('show')) {
            dropdownElement.classList.remove('show');
            // Clear search when closing
            searchInput.value = '';
            renderOptions(wrapper, '');
        }
    });
    
    // Handle escape key to close dropdown
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && dropdownElement.classList.contains('show')) {
            dropdownElement.classList.remove('show');
            searchInput.value = '';
            renderOptions(wrapper, '');
        }
    });
    
    // FIX: Prevent dropdown close when clicking inside dropdown
    dropdownElement.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // If clicking on a checkbox directly, trigger the change event
        if (e.target.type === 'checkbox') {
            e.stopPropagation();
            handleCheckboxChange(wrapper, e.target);
            updateSelectedValues(wrapper);
            updateMultiSelectDisplay(wrapper);
        }
    });
}

// Handle checkbox change with proper "All" option logic
function handleCheckboxChange(wrapper, checkbox) {
    const selectElement = wrapper.querySelector('select');
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    const checkboxes = dropdownElement.querySelectorAll('input[type="checkbox"]');
    
    // Special handling for "All" option
    if (checkbox.value === 'All') {
        if (checkbox.checked) {
            // If "All" is checked, uncheck all other options
            checkboxes.forEach(cb => {
                if (cb !== checkbox && cb.value !== 'All') {
                    cb.checked = false;
                }
            });
        }
    } else {
        // If a specific option is checked, uncheck "All"
        const allCheckbox = Array.from(checkboxes).find(cb => cb.value === 'All');
        if (allCheckbox && checkbox.checked) {
            allCheckbox.checked = false;
        }
    }
    
    // Update original select element
    Array.from(selectElement.options).forEach(option => {
        const checkbox = Array.from(dropdownElement.querySelectorAll('input[type="checkbox"]')).find(cb => cb.value === option.value);
        option.selected = checkbox ? checkbox.checked : false;
    });
}

// Clear all selections
function clearAllSelections(wrapper) {
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    const checkboxes = dropdownElement.querySelectorAll('input[type="checkbox"]');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Update original select
    const selectElement = wrapper.querySelector('select');
    Array.from(selectElement.options).forEach(option => {
        option.selected = false;
    });
}

// Update selected values in wrapper dataset
function updateSelectedValues(wrapper) {
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    const checkboxes = dropdownElement.querySelectorAll('input[type="checkbox"]:checked');
    const selectedValues = Array.from(checkboxes).map(cb => cb.value);
    
    // Store in data attribute
    wrapper.dataset.selectedValues = JSON.stringify(selectedValues);
}

// Update display text
function updateMultiSelectDisplay(wrapper) {
    const displayElement = wrapper.querySelector('.multi-select-display');
    const clearBtn = wrapper.querySelector('.multi-select-clear');
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    const checkboxes = dropdownElement.querySelectorAll('input[type="checkbox"]:checked');
    
    if (checkboxes.length === 0) {
        displayElement.textContent = 'All';
        if (clearBtn) clearBtn.classList.remove('show');
    } else {
        const selectedTexts = Array.from(checkboxes).map(cb => {
            const label = cb.nextElementSibling;
            return label ? label.textContent : cb.value;
        });
        
        // Special case: if "All" is selected, just show "All"
        const allSelected = Array.from(checkboxes).some(cb => cb.value === 'All');
        if (allSelected) {
            displayElement.textContent = 'All';
        } else if (checkboxes.length === 1) {
            displayElement.textContent = selectedTexts[0];
        } else if (checkboxes.length <= 3) {
            // Show up to 3 selected items
            displayElement.textContent = selectedTexts.join(', ');
        } else {
            displayElement.textContent = `${checkboxes.length} selected`;
        }
        
        if (clearBtn) clearBtn.classList.add('show');
    }
}

// Function to set values programmatically (for edit modal)
function setMultiSelectValues(selectElementId, selectedValues) {
    const wrapper = document.querySelector(`#${selectElementId}`).closest('.multi-select-wrapper');
    if (!wrapper) return;
    
    const selectElement = wrapper.querySelector('select');
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    
    // Clear all selections first
    clearAllSelections(wrapper);
    
    // Set selected values
    if (selectedValues && selectedValues.length > 0) {
        selectedValues.forEach(value => {
            const checkbox = dropdownElement.querySelector(`input[type="checkbox"][value="${CSS.escape(value)}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        
        // Update original select
        Array.from(selectElement.options).forEach(option => {
            option.selected = selectedValues.includes(option.value);
        });
    }
    
    updateSelectedValues(wrapper);
    updateMultiSelectDisplay(wrapper);
}

// Function to get selected values
function getMultiSelectValues(selectElementId) {
    const wrapper = document.querySelector(`#${selectElementId}`).closest('.multi-select-wrapper');
    if (!wrapper) return [];
    
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    const checkboxes = dropdownElement.querySelectorAll('input[type="checkbox"]:checked');
    
    return Array.from(checkboxes).map(cb => cb.value);
}

// Handle form submission to ensure all selected values are included
function handleMultiSelectFormSubmit(formElement) {
    const multiSelectWrappers = formElement.querySelectorAll('.multi-select-wrapper');
    
    multiSelectWrappers.forEach(wrapper => {
        const selectElement = wrapper.querySelector('select');
        const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
        const checkboxes = dropdownElement.querySelectorAll('input[type="checkbox"]:checked');
        
        // Clear all selections first
        Array.from(selectElement.options).forEach(option => {
            option.selected = false;
        });
        
        // Set selected options
        checkboxes.forEach(checkbox => {
            const option = Array.from(selectElement.options).find(opt => opt.value === checkbox.value);
            if (option) {
                option.selected = true;
            }
        });
    });
}

// Refresh multi-select for a specific wrapper
function refreshMultiSelect(wrapper) {
    if (!wrapper) return;
    
    const selectElement = wrapper.querySelector('select');
    const dropdownElement = wrapper.querySelector('.multi-select-dropdown');
    
    if (dropdownElement) {
        // Clear and re-render options
        renderOptions(wrapper, '');
        updateMultiSelectDisplay(wrapper);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all multi-select dropdowns on page load
    initMultiSelectDropdowns();
    
    // Handle form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            handleMultiSelectFormSubmit(this);
        });
    });
    
    // Reinitialize when modals are shown (for dynamically loaded content)
    document.addEventListener('shown.bs.modal', function() {
        // Use setTimeout to ensure modal content is fully rendered
        setTimeout(() => {
            initMultiSelectDropdowns();
        }, 50);
    });
    
    // Also reinitialize when tabs are shown (if using tabs)
    document.addEventListener('shown.bs.tab', function() {
        setTimeout(() => {
            initMultiSelectDropdowns();
        }, 50);
    });
});

// Export functions for global use
window.MultiSelect = {
    init: initMultiSelectDropdowns,
    setValues: setMultiSelectValues,
    getValues: getMultiSelectValues,
    updateDisplay: updateMultiSelectDisplay,
    refresh: refreshMultiSelect
};