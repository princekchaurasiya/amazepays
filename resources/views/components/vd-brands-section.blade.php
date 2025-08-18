@props(['brands' => []])

@if(!empty($brands))
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
                        @foreach($brands as $brand)
                            <div class="col-lg-2 col-md-3 col-6 mb-4">
                                <div class="vd-brand-card text-center">
                                    <a href="{{ route('vdbrands') }}?brand={{ $brand['brand_code'] }}" class="d-block">
                                        <div class="vd-brand-image-wrapper">
                                            @if(!empty($brand['featured_image']))
                                                <img src="{{ $brand['featured_image'] }}" 
                                                     alt="{{ $brand['brand_name'] }}" 
                                                     class="vd-brand-image img-fluid"
                                                     loading="lazy"
                                                     onerror="this.onerror=null; this.src='{{ asset('images/placeholder-brand.png') }}';">
                                            @elseif(!empty($brand['thumbnail_image']))
                                                <img src="{{ $brand['thumbnail_image'] }}" 
                                                     alt="{{ $brand['brand_name'] }}" 
                                                     class="vd-brand-image img-fluid"
                                                     loading="lazy"
                                                     onerror="this.onerror=null; this.src='{{ asset('images/placeholder-brand.png') }}';">
                                            @else
                                                <div class="vd-brand-placeholder">
                                                    <span>{{ substr($brand['brand_name'], 0, 1) }}</span>
                                                </div>
                                            @endif
                                            
                                            @if($brand['discount'] > 0)
                                                <div class="vd-discount-badge">
                                                    <span>{{ $brand['discount'] }}% OFF</span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="vd-brand-info mt-3">
                                            <h6 class="vd-brand-name mb-1">{{ $brand['brand_name'] }}</h6>
                                            <p class="vd-brand-category text-muted small mb-1">{{ $brand['category'] }}</p>
                                            <p class="vd-brand-price small">
                                                ₹{{ number_format($brand['min_price']) }} - ₹{{ number_format($brand['max_price']) }}
                                            </p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.vd-brands-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 40px 0;
}

.vd-brand-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    height: 100%;
    border: 1px solid #e9ecef;
}

.vd-brand-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.vd-brand-image-wrapper {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.vd-brand-image {
    max-height: 120px;
    width: auto;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.vd-brand-card:hover .vd-brand-image {
    transform: scale(1.05);
}

.vd-brand-placeholder {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: bold;
}

.vd-discount-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.vd-brand-info {
    padding: 10px 0;
}

.vd-brand-name {
    font-weight: 600;
    color: #333;
    line-height: 1.2;
    margin-bottom: 5px;
}

.vd-brand-category {
    color: #6c757d;
    font-size: 0.85rem;
}

.vd-brand-price {
    color: #28a745;
    font-weight: 600;
}

.vd-brands-slider {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

@media (max-width: 768px) {
    .vd-brands-slider {
        gap: 15px;
    }
    
    .vd-brand-card {
        padding: 12px;
    }
    
    .vd-brand-image-wrapper {
        min-height: 100px;
    }
    
    .vd-brand-image {
        max-height: 100px;
    }
    
    .vd-brand-placeholder {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
@endif 