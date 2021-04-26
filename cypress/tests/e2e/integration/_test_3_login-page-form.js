describe('Login page test suite', () => {

    const baseUrl = Cypress.config().baseUrl;
    before(() => {
        cy.visit(baseUrl); // go to the home page
    });
    
    it('should load login form fields', () => {
        cy.visit('/login');
        cy.get('form')
        .should('have.attr', 'name', 'loginForm');

        cy.get('input')
        .should('have.class', 'form-control');

        cy.get('input')
        .should('have.class', 'form-control');

    });
 
    it('incorrect username/password login check to fail', () => {

        const username = Cypress.env('username');
        const password = Cypress.env('passwordIncorrect');
        
        cy.get('[id="username"]').type(username);
        cy.get('[id="password"]').type(password);
        cy.get('[type=submit]').click();
        cy.get('[id="error_message"]')
        .should('be.visible')
        .contains('Authentication failed!');

    });

    //Can only run this test if password is supplied in env
    // it('correct username/password login check to pass', () => {

    //     const username = Cypress.env('username')
    //     const password = Cypress.env('password')

    //     expect(username, 'username was set').to.be.a('string').and.not.be.empty
    //     expect(password, 'password was set').to.be.a('string').and.not.be.empty

    //     cy.visit('/login')
    //     cy.get('[id="username"]').type(username)
    //     cy.get('[id="password"]').type(password)
    //     cy.get('[type=submit]').click()
    //     cy.url().should('match', /events2$/)

    // });


});

