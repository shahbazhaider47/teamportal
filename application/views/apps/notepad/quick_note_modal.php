<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!-- Quick Note Modal (ClickUp-style) -->
<div id="qn-notepad-modal" class="qn-modal" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="qn-modal__backdrop"></div>

  <div class="qn-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="qn-notepad-title">
    <!-- Header -->
    <div class="qn-modal__header">
      <div id="qn-notepad-title" class="qn-title"><i class="ti ti-notes"></i> Personal Notepad</div>

      <div class="qn-actions">

          <!-- NEW: active/archived toggle -->
          <button class="btn btn-white btn-header text-primary" type="button" id="qn-toggle-archived">
            <i class="ti ti-archive"></i> Show Archived
          </button>  
          
        <button class="qn-icon-btn" type="button" title="Close" aria-label="Close" data-qn-close>
          <svg viewBox="0 0 24 24" class="qn-ic"><path d="M18.3 5.7 12 12l6.3 6.3-1.3 1.3L10.7 13.3 4.4 19.6 3.1 18.3 9.4 12 3.1 5.7 4.4 4.4l6.3 6.3 6.3-6.3z"/></svg>
        </button>
      </div>
    </div>

    <!-- Body -->
    <div class="qn-modal__body">
      <!-- Notes List -->
      <div class="qn-list" id="qn-list" hidden>
        <div class="qn-list__header">
          <h3 class=" d-flex qn-list__title">My Notes</h3>
        </div>
    
        <div class="qn-list__content" id="qn-notes-container"></div>
        <div class="app-divider-v mt-3 mb-4"></div>
        <button class="btn btn-outline-primary btn-sm" type="button" id="qn-list-add">
          <i class="ti ti-plus"></i> New Note
        </button>
      
      </div>

      <!-- Empty state -->
      <div class="qn-empty" id="qn-empty">
        <div class="qn-empty__art" aria-hidden="true">
          <svg viewBox="0 0 120 120" class="qn-art">
            <rect x="28" y="22" width="64" height="86" rx="8" fill="#EEF2F7"/>
            <rect x="42" y="16" width="36" height="14" rx="6" fill="#D9DEE7"/>
            <rect x="40" y="38" width="40" height="6" rx="3" fill="#C5CCD8"/>
            <rect x="40" y="52" width="28" height="6" rx="3" fill="#C5CCD8"/>
            <circle cx="62" cy="72" r="9" fill="#AEB7C6"/>
            <path d="M62 66v12M56 72h12" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </div>
        <div class="fs-5 fw-bold text-muted">Create Your Personal Notes</div>
        <p class="small text-muted mb-4">Capture your thoughts or ideas and access them <br> anywhere in the application!</p>
        <button class="btn btn-primary mb-4" type="button" id="qn-create">Create a Note</button>
      </div>

      <!-- Editor -->
      <form id="qn-editor" class="qn-editor" hidden onsubmit="return false;">
        <input type="hidden" name="id"          id="note_id">
        <input type="hidden" name="folder_id"   id="note_folder_id" value="">
        <input type="hidden" name="is_pinned"   id="note_is_pinned" value="0">
        <input type="hidden" name="is_favorite" id="note_is_favorite" value="0">
        <input type="hidden" name="is_locked"   id="note_is_locked" value="0">
        <input type="hidden" name="status"      id="note_status" value="active">
        <input type="hidden" name="color"       id="note_color" value="">
        <input type="hidden" name="sort_order"  id="note_sort_order" value="0">

        <!-- Toolbar -->
        <div class="qn-toolbar">
          <div class="qn-toolbar__group">
            <button type="button" class="btn btn-light-primary icon-btn b-r-4" data-format="bold" title="Bold">
              <svg viewBox="0 0 24 24" class="qn-ic"><path d="M15.6 11.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 7.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/></svg>
            </button>
            <button type="button" class="btn btn-light-primary icon-btn b-r-4" data-format="italic" title="Italic">
              <svg viewBox="0 0 24 24" class="qn-ic"><path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/></svg>
            </button>

            <button type="button" class="btn btn-light-primary icon-btn b-r-4" id="qn-pin-btn" title="Pin note">
              <svg viewBox="0 0 24 24" class="qn-ic"><path d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z"/></svg>
            </button>

            <button type="button" class="btn btn-light-primary icon-btn b-r-4" id="qn-favorite-btn" title="Favorite note">
              <svg viewBox="0 0 24 24" class="qn-ic"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            </button>

            <div class="qn-dropdown" id="qn-color-dd">
              <button type="button" class="btn btn-light-primary icon-btn b-r-4" id="qn-color-btn" title="Change note color">
                <svg viewBox="0 0 24 24" class="qn-ic"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
              </button>
              <div class="qn-dropdown__menu qn-colors-menu">
                <div class="qn-colors">
                  <div class="qn-color-option" data-color="" style="background-color:#ffffff"></div>
                  <div class="qn-color-option" data-color="#FFE6A7" style="background-color:#FFE6A7"></div>
                  <div class="qn-color-option" data-color="#D4EDDA" style="background-color:#D4EDDA"></div>
                  <div class="qn-color-option" data-color="#D1ECF1" style="background-color:#D1ECF1"></div>
                  <div class="qn-color-option" data-color="#F8D7DA" style="background-color:#F8D7DA"></div>
                  <div class="qn-color-option" data-color="#E2E3E5" style="background-color:#E2E3E5"></div>
                </div>
              </div>
            </div>

            <div class="qn-dropdown d-flex" id="qn-folder-dd">
            
            <button type="button" class="btn btn-light-primary btn-header" id="qn-folder-btn" title="Add to folder">
              <i id="qn-folder-icon" class="ti ti-folder-filled me-2"></i>
              <span id="qn-folder-label">Select Folder</span>
            </button>

              <div class="qn-dropdown__menu qn-folders-menu">
                <div class="qn-folders">
                  <div class="qn-folder-option" data-folder-id="">
                    <div class="qn-folder-icon">
                      <i class="ti ti-folder-off"></i>
                    </div>
                    <span>No Folder</span>
                  </div>

                  <div class="qn-list" id="qn-folders" hidden>
                    <div class="qn-list__header"><h3 class="qn-list__title">Folders</h3></div>
                    <div class="qn-list__content" id="qn-folders-container"></div>
                  </div>
                </div>

                <div class="qn-folders-actions">
                <div class="app-divider-v dotted"></div>    
                  <button type="button" class="dropdown-item border-0 text-center small text-primary" id="qn-create-folder"><i class="ti ti-plus me-2 text-primary"></i> Create New Folder</button>
                </div>
              </div>
            </div>
            
          </div>
            
        </div>
        
        <input class="title-input" name="title" id="note_title" placeholder="Untitled...">
        
        <textarea class="qn-textarea" name="content" id="note_content" placeholder="Start typing your note..."></textarea>

        <div class="qn-editor__actions text-end gap-2">
          <button class="btn btn-light-primary btn-sm" type="button" id="qn-cancel">Cancel</button>            
          <button class="btn btn-primary btn-sm" type="button" id="qn-save">Save Note</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Create Folder Modal (secondary modal) -->
<div id="qn-folder-modal" class="qn-modal qn-modal--secondary" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
  <div class="qn-modal__backdrop" data-qn-close-folder></div>
  <div class="qn-modal__dialog qn-modal__dialog--small" role="dialog" aria-modal="true" aria-labelledby="qn-folder-title">
    <div class="qn-modal__header">
      <div class="qn-title" id="qn-folder-title">Create New Folder</div>
      <button class="qn-icon-btn" type="button" title="Close" aria-label="Close" data-qn-close-folder>
        <svg viewBox="0 0 24 24" class="qn-ic"><path d="M18.3 5.7 12 12l6.3 6.3-1.3 1.3L10.7 13.3 4.4 19.6 3.1 18.3 9.4 12 3.1 5.7 4.4 4.4l6.3 6.3 6.3-6.3z"/></svg>
      </button>
    </div>
    <div class="qn-modal__body">
      <form id="qn-folder-form" onsubmit="return false;">
        <div class="qn-form-group">
          <label for="folder_name" class="qn-label">Folder Name <span class="text-danger">*</span></label>
          <input type="text" class="qn-input" id="folder_name" required>
        </div>

        <div class="qn-form-group mt-3">
          <div class="qn-icons-grid qn-colors--grid">Color:
            <button type="button" class="qn-color-option selected" data-color="#FFE6A7" style="background-color:#FFE6A7"></button>
            <button type="button" class="qn-color-option" data-color="#D4EDDA" style="background-color:#D4EDDA"></button>
            <button type="button" class="qn-color-option" data-color="#D1ECF1" style="background-color:#D1ECF1"></button>
            <button type="button" class="qn-color-option" data-color="#F8D7DA" style="background-color:#F8D7DA"></button>
            <button type="button" class="qn-color-option" data-color="#E2E3E5" style="background-color:#E2E3E5"></button>
          </div>
          <input type="hidden" id="folder_color" value="#FFE6A7">
        </div>

        <div class="qn-form-group mt-3">
          <div class="qn-icons-grid" id="qn-folder-icons">Icon:
            <button type="button" class="qn-icon-pill selected" data-icon="ti ti-folder"><i class="ti ti-folder"></i></button>
            <button type="button" class="qn-icon-pill" data-icon="ti ti-notes"><i class="ti ti-notes"></i></button>
            <button type="button" class="qn-icon-pill" data-icon="ti ti-star"><i class="ti ti-star"></i></button>
            <button type="button" class="qn-icon-pill" data-icon="ti ti-bulb"><i class="ti ti-bulb"></i></button>
            <button type="button" class="qn-icon-pill" data-icon="ti ti-bookmark"><i class="ti ti-bookmark"></i></button>
            <button type="button" class="qn-icon-pill" data-icon="ti ti-file-dollar"><i class="ti ti-file-dollar"></i></button>
          </div>
          <input type="hidden" id="folder_icon" value="ti ti-folder">
        </div>

        <div class="qn-editor__actions mt-3 text-end">
            <hr>    
          <button class="btn btn-lightprimary btn-ssm" type="button" data-qn-close-folder>Cancel</button>            
          <button class="btn btn-primary btn-ssm" type="button" id="qn-folder-save">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>