export interface BaseTestimonial {
    name: string;
    age: number;
    location: string;
    comments: string;
    imageUrl: string;
}

export interface Testimonial extends BaseTestimonial{
    id: string;
}
