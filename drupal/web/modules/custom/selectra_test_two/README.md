Hello ! 
==========================
Here is some assumptions and instructions of my implementation of this test case. 

Assumptions
==========================

0. I've made this test task in very complicated way, because I want to show my skills in Drupal solutions.
I decided to use Drupal way, but without any contributed modules. In this Document I'm trying to explain 
why I've made some decisions for implementation.

1. I assume that for "Problem Two: Sales Taxes" using Drupal Commerce (Tax functionality is included in 
Tax module that comes with Commerce, and for whole test implementation it would take only 10 minutes 
for configuration) is forbidden.
   
2. I assume that Tax rates can be changed, so I need to create a additional Configuration for keeping tax rates.
   
3. I assume that creating Product entities with hook_update () is not good practice, so I will add a CSV file
and CSV importer as part of this test case.

4. I assume that product types are finalized, and we don't need to add them in future. (I don't want do do 
that with Taxonomy vocabulary/ Additional Config Entity, because of additional import/additional Config Entity. 
And I have already implementation example.)

5. I assume that cart functionality not needed, there is enough examples for Entity creation.

6. For Product form I've created two Different Services, one for creating product list, 
second for calculating prices with taxes.

7. Instead of implementing calculation on form submit, I decided to use created service for tax calculation, 
because in my opinion it is not so boring :D.

8. After 30 minutes of calculations I found that you have a mistake in task description. 
" imported bottle of perfume: 54.65 " should be " imported bottle of perfume: 54.63 "

Instructions
==========================