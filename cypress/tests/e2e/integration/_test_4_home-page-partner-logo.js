describe('Home Page-partner logos', () => {

    const baseUrl = Cypress.config().baseUrl;
    beforeEach(() => {
        cy.visit(baseUrl); // go to the home page
        cy.visit('/home')
    });
 
    it('/home page partner-logo-url check - healthmap', () => {
        cy.get('a[href="https://www.healthmap.org/en"]')
        .should('have.attr', 'third' , 'true')
    });

    it('/home page partner-logo-url check - promed', () => {
        cy.get('a[href="https://promedmail.org"]')
        .should('have.attr', 'third' , 'true')
    });

    it('/home page partner-logo-url check - endingpandemic', () => {
        cy.get('a[href="https://endingpandemics.org/"]')
        .should('have.attr', 'third' , 'true')
       
    });

    it('/home page partner-logo-url check - tephinet', () => {
        cy.get('a[href="https://www.tephinet.org"]')
        .should('have.attr', 'third' , 'true')
        
    });
});

