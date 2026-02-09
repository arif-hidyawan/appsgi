<style>
    /* Styling Heading Grup */
    .fi-fo-checkbox-list .permission-group-heading {
        grid-column: 1 / -1;
        margin-top: 1.5rem;
        padding-top: 0.75rem;
        padding-bottom: 0.25rem;
        padding-left: 0.5rem;
        
        font-weight: 800;
        font-size: 0.85rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        
        color: #1e293b; /* Slate-800 matching dark sidebar text concept */
        background-color: #f1f5f9; /* Slate-100 */
        border-left: 4px solid #0f172a; /* Slate-900 border accent */
        border-radius: 4px;
    }

    .fi-fo-checkbox-list .permission-group-heading:first-of-type {
        margin-top: 0;
    }

    /* Permission Dashboard Full Width */
    .fi-fo-checkbox-list div[data-group="dashboard"] {
        grid-column: 1 / -1;
        background-color: #f8fafc;
        border: 1px dashed #cbd5e1;
        padding: 10px;
        border-radius: 6px;
    }
</style>

<script>
    function enhancePermissionCheckboxList() {
        const labels = Array.from(
            document.querySelectorAll('.fi-fo-checkbox-list .fi-fo-checkbox-list-option-label')
        );

        if (!labels.length) return;

        // === DAFTAR GRUP SESUAI NAVIGASI SIDEBAR ===
        const groups = [
            { keyword: 'Dashboard -',       title: 'DASHBOARD' },
            { keyword: 'CRM -',             title: 'CRM' },
            { keyword: 'Finance -',         title: 'FINANCE' },
            { keyword: 'Master Data -',     title: 'MASTER DATA' },
            { keyword: 'Sales -',           title: 'SALES' },
            { keyword: 'Procurement -',     title: 'PROCUREMENT' },
            { keyword: 'Inventory -',       title: 'INVENTORY' },
            { keyword: 'Pengaturan -',      title: 'PENGATURAN' },
        ];

        labels.forEach(function (el) {
            const text = (el.textContent || '').trim();
            const wrapper = el.closest('div[wire\\:key]');
            
            if (!wrapper) return;

            // Logic Dashboard Full Width
            if (text.includes('Dashboard -')) {
                wrapper.setAttribute('data-group', 'dashboard');
            }

            // Logic Heading Group
            const matchedGroup = groups.find(g => text.includes(g.keyword));

            if (matchedGroup) {
                const prevWrapper = wrapper.previousElementSibling;
                let needHeading = false;

                // Cek apakah elemen sebelumnya punya keyword yang sama
                if (!prevWrapper) {
                    needHeading = true;
                } else {
                    const prevLabel = prevWrapper.querySelector('.fi-fo-checkbox-list-option-label');
                    const prevText = prevLabel ? prevLabel.textContent : '';
                    
                    if (!prevText.includes(matchedGroup.keyword)) {
                        needHeading = true;
                    }
                }

                // Cegah duplikasi heading
                if (needHeading) {
                    if (prevWrapper && prevWrapper.classList.contains('permission-group-heading')) {
                        return;
                    }
                    const heading = document.createElement('div');
                    heading.classList.add('permission-group-heading');
                    heading.textContent = matchedGroup.title;
                    
                    wrapper.parentNode.insertBefore(heading, wrapper);
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', enhancePermissionCheckboxList);
    document.addEventListener('livewire:navigated', enhancePermissionCheckboxList);
    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('morph.updated', ({ el, component }) => {
            enhancePermissionCheckboxList();
        });
    });
</script>