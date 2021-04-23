describe('Home Page-Specs', () => {
    const baseUrl = Cypress.config().baseUrl;
    it('should display the app name on the home page', () => {
      cy.visit(baseUrl+'home/'); // go to the home page
  
      cy.window()
      .then(win => {
        console.log('got app window object', win)
        return win
      })
      .its('angular')
      .then(ng => {
        console.log('got angular object', ng.version)
      })

      cy.get('body')
      .should('have.attr', 'ng-app' , 'EpicoreApp')

    });
   
});

