Feature: Book loan flow
  In order to manage books in a library
  As a librarian
  I want to borrow and return books safely

  Scenario: Borrowing and returning a book
    Given a fresh book and a reader
    When I borrow the book
    Then the book should be marked as borrowed
    When I return the book
    Then the book should not be borrowed anymore

  Scenario: Borrowing an already borrowed book should fail
    Given a fresh book and a reader
    And the book is already borrowed
    When I try to borrow the same book again
    Then borrowing should fail

  Scenario: Reader card number must have exactly six digits
    Given a reader with library card number "12345"
    When I validate the reader
    Then reader validation should fail
