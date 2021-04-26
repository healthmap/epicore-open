describe('Home Page-public-pages', () => {
    
    // const baseUrl = Cypress.config().baseUrl;
    // before(() => {
    //     cy.visit(baseUrl); // go to the home page
    // }); 

    it('/about page navigation and content checks', () => {
        
        cy.visit('/about');
        
        cy.get('h3')
        .contains('EpiCore is a system that aims to complement existing surveillance methods and speed up the process of finding, reporting and verifying public health events.');

        //Many more tags to check...
        
    });


    it('/news page navigation and content checks', () => {

        cy.visit('/news');
        
        cy.get('h3')
        .contains('This quarterly newsletter is an effort to keep EpiCore members informed about how EpiCore is doing, updates on the system, and any training or educational opportunities available.');

    });

    it('/how page navigation and content checks', () => {

        cy.visit('/how');
        
        cy.get('h3')
        .contains('EpiCore links a worldwide member network of health professionals in order to provide verification of suspected or rumored disease outbreaks.');

    });

    it('/who page navigation and content checks', () => {

        cy.visit('/who');
        
        cy.get('p')
        .contains('All health professionals with formal education and training in animal or human health, and knowledge of basic principles of epidemiology, infectious disease or related fields are encouraged to apply online.');

    });


    it('/events_public page navigation and content checks', () => {

        cy.visit('/events_public');
        cy.get('span')
        .contains('Public RFI List');
        
        cy.get('select').select('Most Recent');
    });

});

