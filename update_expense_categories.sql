-- Update expense categories based on expense titles
-- This will assign appropriate categories to expenses

-- Update Groceries to Food & Dining category
UPDATE expenses 
SET category = 'food_dining' 
WHERE LOWER(title) LIKE '%groceries%' 
  OR LOWER(title) LIKE '%grocery%'
  OR LOWER(title) LIKE '%supermarket%'
  OR LOWER(title) LIKE '%woolworths%'
  OR LOWER(title) LIKE '%coles%'
  OR LOWER(title) LIKE '%aldi%';

-- Update Room/Accommodation to Housing category
UPDATE expenses 
SET category = 'housing' 
WHERE LOWER(title) LIKE '%room%booking%'
  OR LOWER(title) LIKE '%accommodation%'
  OR LOWER(title) LIKE '%hotel%'
  OR LOWER(title) LIKE '%airbnb%'
  OR LOWER(title) LIKE '%rent%';

-- Update Fresh food stores to Food & Dining
UPDATE expenses 
SET category = 'food_dining' 
WHERE LOWER(title) LIKE '%david%fresh%'
  OR LOWER(title) LIKE '%fresh%market%'
  OR LOWER(title) LIKE '%fruit%market%';

-- Update general food items to Food & Dining
UPDATE expenses 
SET category = 'food_dining' 
WHERE LOWER(title) LIKE '%ww%'
  OR LOWER(title) LIKE '%food%'
  OR LOWER(title) LIKE '%restaurant%'
  OR LOWER(title) LIKE '%cafe%'
  OR LOWER(title) LIKE '%pizza%'
  OR LOWER(title) LIKE '%burger%';

-- Update Pyramid (assuming it's a restaurant/cafe) to Food & Dining
UPDATE expenses 
SET category = 'food_dining' 
WHERE LOWER(title) LIKE '%pyramid%';

-- Additional common categories you might want to add:

-- Transportation
UPDATE expenses 
SET category = 'transportation' 
WHERE LOWER(title) LIKE '%uber%'
  OR LOWER(title) LIKE '%taxi%'
  OR LOWER(title) LIKE '%petrol%'
  OR LOWER(title) LIKE '%gas%'
  OR LOWER(title) LIKE '%parking%'
  OR LOWER(title) LIKE '%train%'
  OR LOWER(title) LIKE '%bus%';

-- Entertainment
UPDATE expenses 
SET category = 'entertainment' 
WHERE LOWER(title) LIKE '%movie%'
  OR LOWER(title) LIKE '%cinema%'
  OR LOWER(title) LIKE '%concert%'
  OR LOWER(title) LIKE '%game%'
  OR LOWER(title) LIKE '%netflix%'
  OR LOWER(title) LIKE '%spotify%';

-- Shopping
UPDATE expenses 
SET category = 'shopping' 
WHERE LOWER(title) LIKE '%amazon%'
  OR LOWER(title) LIKE '%ebay%'
  OR LOWER(title) LIKE '%clothes%'
  OR LOWER(title) LIKE '%clothing%'
  OR LOWER(title) LIKE '%shoes%';

-- Utilities
UPDATE expenses 
SET category = 'utilities' 
WHERE LOWER(title) LIKE '%electricity%'
  OR LOWER(title) LIKE '%water%'
  OR LOWER(title) LIKE '%internet%'
  OR LOWER(title) LIKE '%phone%bill%'
  OR LOWER(title) LIKE '%gas%bill%';

-- Healthcare
UPDATE expenses 
SET category = 'healthcare' 
WHERE LOWER(title) LIKE '%doctor%'
  OR LOWER(title) LIKE '%pharmacy%'
  OR LOWER(title) LIKE '%medicine%'
  OR LOWER(title) LIKE '%hospital%'
  OR LOWER(title) LIKE '%dental%';

-- Display summary of categorized expenses
SELECT 
    category,
    COUNT(*) as expense_count,
    SUM(amount) as total_amount
FROM expenses
GROUP BY category
ORDER BY total_amount DESC;
