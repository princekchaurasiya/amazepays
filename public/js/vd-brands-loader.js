/**
 * Value Design Brands Loader for Home Page
 * Dynamically loads and displays VD brands without affecting existing code
 */

class VDBrandsLoader {
    constructor() {
        this.apiUrl = '/api/vd-brands/home';
        this.containerId = 'vd-brands-container';
        this.loadingId = 'vd-brands-loading';
        this.errorId = 'vd-brands-error';
        this.init();
    }

    init() {
        // Create container if it doesn't exist
        this.createContainer();
        
        // Load brands after a short delay to ensure page is ready
        setTimeout(() => {
            this.loadBrands();
        }, 500);
    }

    createContainer() {
        // Check if container already exists
        if (document.getElementById(this.containerId)) {
            return;
        }

        // Find the best place to insert the VD brands section
        const insertAfter = this.findInsertionPoint();
        
        if (insertAfter) {
            const container = document.createElement('div');
            container.id = this.containerId;
            container.className = 'vd-brands-dynamic-container';
            
            // Create loading element
            const loading = document.createElement('div');
            loading.id = this.loadingId;
            loading.className = 'vd-brands-loading text-center py-4';
            loading.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading Value Design brands...</span>
                </div>
                <p class="mt-2 text-muted">Loading Value Design Gift Cards...</p>
            `;
            
            // Create error element (hidden by default)
            const error = document.createElement('div');
            error.id = this.errorId;
            error.className = 'vd-brands-error text-center py-4 d-none';
            error.innerHTML = `
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Unable to load Value Design brands at this time.
                    <button class="btn btn-sm btn-outline-warning ms-2" onclick="vdBrandsLoader.retryLoad()">
                        <i class="fas fa-redo me-1"></i>Retry
                    </button>
                </div>
            `;
            
            container.appendChild(loading);
            container.appendChild(error);
            
            // Insert after the specified element
            insertAfter.parentNode.insertBefore(container, insertAfter.nextSibling);
        }
    }

    findInsertionPoint() {
        // Try to find the existing brands section first
        const existingBrandsSection = document.querySelector('.brand-slick-slider');
        if (existingBrandsSection) {
            return existingBrandsSection.closest('.row');
        }
        
        // If no existing brands section, find the hot deals section
        const hotDealsSection = document.querySelector('.hot-deal-text');
        if (hotDealsSection) {
            return hotDealsSection.closest('.row');
        }
        
        // Fallback: insert before the footer or at the end of main content
        const footer = document.querySelector('footer');
        if (footer) {
            return footer;
        }
        
        const mainContent = document.querySelector('main') || document.querySelector('.container-fluid');
        return mainContent;
    }

    async loadBrands() {
        try {
            this.showLoading();
            
            const response = await fetch(this.apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success && data.brands && data.brands.length > 0) {
                this.renderBrands(data.brands);
            } else {
                this.showError('No Value Design brands available at this time.');
            }
            
        } catch (error) {
            console.error('Error loading VD brands:', error);
            this.showError('Failed to load Value Design brands. Please try again later.');
        }
    }

    renderBrands(brands) {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        // Hide loading
        this.hideLoading();
        
        // Create the brands HTML
        const brandsHTML = this.generateBrandsHTML(brands);
        
        // Replace container content
        container.innerHTML = brandsHTML;
        
        // Initialize any additional functionality
        this.initializeBrandCards();
    }

    generateBrandsHTML(brands) {
        let html = `
            <div class="vd-brands-section mt-5 mb-5">
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <h2 class="text-grey-900 fw-700 pb-0 mb-2 d-block text-center hot-deal-text">
                                Value Design Gift Cards
                            </h2>
                            <hr class="normalhr">

                            <div class="row mt-5 mb-5 justify-content-center mx-0 gx-0">
                                <div class="vd-brands-slider">
        `;

        brands.forEach(brand => {
            const imageUrl = brand.featured_image || brand.thumbnail_image || '';
            const fallbackImage = '/images/placeholder-brand.png';
            const discountBadge = brand.discount > 0 ? 
                `<div class="vd-discount-badge"><span>${brand.discount}% OFF</span></div>` : '';
            
            html += `
                <div class="col-lg-2 col-md-3 col-6 mb-4">
                    <div class="vd-brand-card text-center">
                        <a href="/vdbrands?brand=${encodeURIComponent(brand.brand_code)}" class="d-block">
                            <div class="vd-brand-image-wrapper">
                                ${imageUrl ? 
                                    `<img src="${imageUrl}" alt="${brand.brand_name}" class="vd-brand-image img-fluid" loading="lazy" onerror="this.onerror=null; this.src='${fallbackImage}';">` :
                                    `<div class="vd-brand-placeholder"><span>${brand.brand_name.charAt(0)}</span></div>`
                                }
                                ${discountBadge}
                            </div>
                            
                            <div class="vd-brand-info mt-3">
                                <h6 class="vd-brand-name mb-1">${brand.brand_name}</h6>
                                <p class="vd-brand-category text-muted small mb-1">${brand.category}</p>
                                <p class="vd-brand-price small">
                                    ₹${this.formatNumber(brand.min_price)} - ₹${this.formatNumber(brand.max_price)}
                                </p>
                            </div>
                        </a>
                    </div>
                </div>
            `;
        });

        html += `
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    initializeBrandCards() {
        // Add loading animation
        const brandCards = document.querySelectorAll('.vd-brand-card');
        brandCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Add click tracking
        const brandLinks = document.querySelectorAll('.vd-brand-card a');
        brandLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const brandName = this.querySelector('.vd-brand-name').textContent;
                console.log('VD Brand clicked:', brandName);
                // You can add analytics tracking here
            });
        });
    }

    showLoading() {
        const loading = document.getElementById(this.loadingId);
        const error = document.getElementById(this.errorId);
        
        if (loading) loading.classList.remove('d-none');
        if (error) error.classList.add('d-none');
    }

    hideLoading() {
        const loading = document.getElementById(this.loadingId);
        if (loading) loading.classList.add('d-none');
    }

    showError(message) {
        const loading = document.getElementById(this.loadingId);
        const error = document.getElementById(this.errorId);
        
        if (loading) loading.classList.add('d-none');
        if (error) {
            error.querySelector('.alert').textContent = message;
            error.classList.remove('d-none');
        }
    }

    retryLoad() {
        this.loadBrands();
    }

    formatNumber(num) {
        return new Intl.NumberFormat('en-IN').format(num);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.vdBrandsLoader = new VDBrandsLoader();
});

// Also initialize if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.vdBrandsLoader = new VDBrandsLoader();
    });
} else {
    window.vdBrandsLoader = new VDBrandsLoader();
} 