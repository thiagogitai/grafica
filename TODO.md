# TODO List for Admin and Public Area Enhancements

## 1. Database and Models
- [ ] Create migration for settings table (homepage content, whatsapp, price_percentage)
- [ ] Create Settings model
- [ ] Ensure Category model exists (check if already present)

## 2. Admin Controllers
- [ ] Create CategoryController for managing categories
- [ ] Add settings methods to AdminController (index, update)

## 3. Routes
- [ ] Add category routes in web.php (admin/categories)
- [ ] Add settings routes in web.php (admin/settings)

## 4. Admin Views
- [ ] Create category management views (index, create, edit)
- [ ] Create settings view for homepage, whatsapp, price percentage

## 5. Public Area Updates
- [ ] Update HomeController to fetch and display settings
- [ ] Modify home.blade.php to use dynamic content from settings
- [ ] Update product prices to include percentage markup

## 6. Checkout Integration
- [ ] Modify CheckoutController to send WhatsApp message instead of payment
- [ ] Update checkout views accordingly

## 7. Testing and Finalization
- [ ] Test admin category management
- [ ] Test settings configuration
- [ ] Test homepage editing
- [ ] Test WhatsApp integration in checkout
