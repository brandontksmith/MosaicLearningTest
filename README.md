PHP Assessment 1
----------------
Review the attached PHP script.

Imagine you inherited this script and were told to do whatever you think is necessary to take ownership of the script 
(it will be your responsibility moving forward). Explain anything you would change/add/remove from the script. Code is 
not necessary, but if you feel it is the best way to explain some of your changes, feel free to include it. Also, if 
you were to make any changes, what would you do afterward to reintegrate the script back into a production environment?

If I inherited this script, I would make the following changes (some of which I implemented in the provided code):

- Implement a function to clean and parse user-submitted data, so that no unexpected or malicious input is attempted to be passed to the script.
- Implement error handling to ensure that all required fields are completed.
- Implement additional validation to ensure that each form field is valid (this I did not do on the code). Ex: check that the Card Code or CVV is at least 3 or 4 digits.
- Implement AES 256 Encryption to store the Credit Card Number.
- Change the database handler to use PDO and not mysql.
- Define the database name to use; the original code did not do this.
- Use PDO prepared statements to protect from SQL injection, although the sanitization in the cleanVars function is the first measure against this.
- Use htmlspecialchars() function to output data back to the browser, preventing cross-site scripting / XSS attacks.
- Not use the root database user - this is extremely insecure. Create a specific user that has rights to the payment/order tables for security reasons.

Re-integrating back to the Production Environment:

- Really, the changes made will not impact the overall design of the system, since this script is merely one piece. The changes made were nothing more than improvements, such as using PDO instead of mysql, and does not impact other db calls in the system. Further, the form was only changed to output error messages and include submitted data should an error occur, which also won’t impact other portions of the system.
- Now, if the system contains a global configuration file, moving the defined constants for the database connection to this file would be a great idea to clean up the script.
- If the system contains a global file to store functions or a file that has functions for cleaning data, moving the cleanVars function to this file would be a smart choice.
- Testing: I would definitely test the script and ensure that everything works properly before integrating back into production. If we have test cases, I would go through the test cases and ensure that all previous test cases pass with the new implementation, as well as any new test cases that are created.